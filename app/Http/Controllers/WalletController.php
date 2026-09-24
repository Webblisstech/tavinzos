<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Wallet: shows the balance and ledger, and funds it through WebBlissPay.
 *
 * The money rule: we create a PENDING top-up before sending the customer to
 * the hosted page, then only ever credit the wallet after a server-side
 * verify confirms `paid` and the amount matches. The callback's query string
 * is never trusted — it only tells us which reference to verify.
 */
class WalletController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $ledger = DB::table('wallet_transactions')
            ->where('user_id', $userId)
            ->where('type', 'topup')          // deposits only — not purchases/refunds
            ->orderByDesc('id')
            ->limit(30)
            ->get()
            ->map(fn ($t) => [
                'type'    => $t->type,
                'status'  => $t->status ?? 'settled',
                'amount'  => (float) $t->amount,
                'money'   => $this->money((float) $t->amount),
                'note'    => $t->note,
                'at'      => $t->created_at,
                'ref'     => $t->gateway_ref ?? $t->reference,
            ]);

        return view('wallet.index', [
            'balance'  => $this->money((float) $request->user()->wallet_balance),
            'ledger'   => $ledger,
            'min'      => (float) $this->setting('wallet.min_topup', 500),
            'max'      => (float) $this->setting('wallet.max_topup', 500000),
            'symbol'   => (string) $this->setting('numbers.currency.symbol', '₦'),
            'tutorial' => (string) $this->setting('site.deposit_tutorial_url', $this->setting('site.tutorial_url', '')),
            'deposits' => (bool) $this->setting('payment.deposits_enabled', true),
            'gateway'  => (string) $this->setting('payment.gateway_name', 'WebBlissPay'),
            'note'     => (string) $this->setting('payment.min_topup_note', ''),
            'va'       => $request->user()->va_account_number ? [
                'number' => $request->user()->va_account_number,
                'name'   => $request->user()->va_account_name,
                'bank'   => $request->user()->va_bank_name,
            ] : null,
        ]);
    }

    /** Create a checkout and send the customer to the hosted page. */
    public function deposit(Request $request)
    {
        // Admin can switch deposits off from payment settings.
        if (! (bool) $this->setting('payment.deposits_enabled', true)) {
            return back()->with('error', __('Deposits are temporarily unavailable. Please try again later.'));
        }

        $min = (float) $this->setting('wallet.min_topup', 500);
        $max = (float) $this->setting('wallet.max_topup', 500000);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:' . $min, 'max:' . $max],
        ]);

        $user   = $request->user();
        $amount = round((float) $data['amount'], 2);

        // Our own reference — unique, and what we key the pending row on.
        $reference = 'TOP-' . strtoupper(Str::random(10));

        // Record the intent as pending BEFORE any redirect. If the customer
        // pays and we crash before crediting, verify() can still find this.
        DB::table('wallet_transactions')->insert([
            'user_id'       => $user->id,
            'reference'     => $reference,
            'gateway_ref'   => $reference,      // we send our ref as the gateway reference
            'type'          => 'topup',
            'status'        => 'pending',
            'amount'        => $amount,
            'balance_after' => (float) $user->wallet_balance,   // provisional; set on settle
            'currency'      => (string) $this->setting('numbers.currency.code', 'NGN'),
            'note'          => 'Wallet top-up',
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        $payload = [
            'amount'       => $amount,
            'name'         => $user->name ?: 'Customer',
            'email'        => $user->email,
            'reference'    => $reference,
            'callback_url' => route('wallet.callback'),
            'description'  => 'Wallet top-up',
            'metadata'     => ['user_id' => $user->id],
        ];

        $secret = (string) \App\Support\Gateway::webblissSecret();
        $base   = rtrim((string) \App\Support\Gateway::webblissBase(), '/');
        $root   = preg_replace('#/api/v\d+/?$#', '', $base);   // domain root

        // Try the configured base first; if that path 404s (provider moved the
        // endpoint), retry once on the domain root before giving up.
        $urls = array_values(array_unique([
            $base . '/checkout/initialize',
            $root . '/api/v1/checkout/initialize',
            $root . '/checkout/initialize',
        ]));

        $response = null;
        $triedUrl = null;
        foreach ($urls as $url) {
            $triedUrl = $url;
            $response = Http::withToken($secret)->acceptJson()->post($url, $payload);
            if ($response->status() !== 404) {
                break;   // 404 means wrong path; anything else, stop and use it
            }
        }

        $body = $response->json();

        if (! $response->successful() || ! ($body['status'] ?? false) || empty($body['data']['checkout_url'])) {
            Log::warning('WebBlissPay init failed', [
                'ref'        => $reference,
                'url'        => $triedUrl,
                'http_status'=> $response->status(),
                'secret_set' => $secret !== '',
                'body'       => $body ?: mb_substr((string) $response->body(), 0, 400),
            ]);

            // Keep the pending row — the gateway may still have created the
            // payment. The customer can retry, and "Check status" can settle it
            // if they actually paid. We only remove truly-stale pendings elsewhere.
            return back()->withErrors(['amount' => __('Could not start the payment. Please try again.')]);
        }

        // Off to the hosted checkout.
        return redirect()->away($body['data']['checkout_url']);
    }

    /** The customer returns here. We verify server-side, then credit. */
    public function callback(Request $request)
    {
        // WebBlissPay may return the reference under any of these names.
        $reference = $request->query('reference')
            ?: $request->query('merchant_reference')
            ?: $request->query('ref')
            ?: $request->query('trxref')
            ?: $request->query('transaction_reference')
            ?: $request->query('tx_ref')
            ?: $request->input('reference')
            ?: $request->input('merchant_reference');

        if (! $reference) {
            Log::warning('WebBlissPay callback: no reference', [
                'query' => $request->query(),
                'all'   => $request->all(),
            ]);
            return redirect()->route('wallet.index')->withErrors(['amount' => __('Missing payment reference.')]);
        }

        // The callback often fires the instant the user is redirected — a beat
        // before the gateway has finished marking the payment paid. Retry the
        // settle a few times with a short pause before giving up, so most
        // payments confirm here without the customer needing "Check status".
        $result = ['ok' => false, 'message' => ''];
        for ($attempt = 1; $attempt <= 4; $attempt++) {
            $result = $this->settle($reference, $request->user()->id);
            if ($result['ok']) {
                break;
            }
            // Stop early on a hard failure (not-found, amount mismatch); only
            // retry the "not confirmed yet" case.
            if (! str_contains($result['message'], __('not confirmed yet'))) {
                break;
            }
            if ($attempt < 4) {
                sleep(2);   // brief pause, then re-verify
            }
        }

        return redirect()->route('wallet.index')->with(
            $result['ok'] ? 'status' : 'error',
            $result['ok']
                ? $result['message']
                : __('We received your return from payment. If you were charged, your wallet will update shortly — you can also tap "Check status".')
        );
    }

    /**
     * Manual re-check from a pending row — same verify+credit path, for when
     * a customer paid but the callback never brought them back.
     */
    public function recheck(Request $request, string $reference)
    {
        $result = $this->settle($reference, $request->user()->id);

        return redirect()->route('wallet.index')->with(
            $result['ok'] ? 'status' : 'error',
            $result['message']
        );
    }

    /**
     * Gateway webhook. Signed with HMAC-SHA256 of the raw body, keyed with
     * our secret. We verify the signature, then settle by reference — the
     * settle() path re-verifies with the gateway and credits exactly once.
     */
    public function webhook(Request $request)
    {
        $payload   = $request->getContent();
        $signature = $request->header('X-Webbliss-Signature')
            ?? $request->header('x-webbliss-signature')
            ?? $request->header('Webbliss-Signature')
            ?? '';
        $secret    = (string) \App\Support\Gateway::webblissSecret();

        $expected = hash_hmac('sha256', $payload, $secret);

        // hash_equals, not === : constant-time, no timing leak.
        if (! $signature || ! hash_equals($expected, $signature)) {
            Log::warning('WebBlissPay webhook: bad signature', [
                'delivery'    => $request->header('X-Webbliss-Delivery'),
                'sig_received'=> $signature ?: '(none)',
                'secret_set'  => $secret !== '',
                'headers'     => array_keys($request->headers->all()),
                'body_sample' => mb_substr($payload, 0, 200),
            ]);
            return response()->json(['error' => 'invalid signature'], 401);
        }

        $event = $request->input('event');
        $data  = $request->input('data', []);
        $channel = $data['channel'] ?? null;

        // Both checkout and virtual-account arrive as payment.success; the
        // channel tells them apart (per WebBlissPay docs).
        if ($event === 'payment.success') {
            if ($channel === 'checkout') {
                // Match the pending row by the merchant_reference we created.
                $reference = $data['merchant_reference'] ?? null;
                if ($reference) {
                    // settle() is idempotent — a repeat delivery finds it already
                    // settled and does nothing (dedupe on reference).
                    $this->settle($reference);
                }
            } elseif ($channel === 'virtual_account') {
                // A bank transfer into the customer's dedicated account.
                $this->creditVirtualAccount($data);
            }
        }

        // 200 immediately so the gateway stops retrying (respond within 5s).
        return response()->json(['received' => true]);
    }

    /**
     * Credit a wallet from a virtual-account transfer.
     *
     * Per the docs: match the customer by account.account_number, dedupe on
     * data.reference, and credit net_amount (what actually hit our balance
     * after the provider's fee), not the gross amount the customer sent.
     */
    private function creditVirtualAccount(array $data): void
    {
        $accountNumber = $data['account']['account_number'] ?? null;
        $email         = $data['customer']['email'] ?? null;
        $ref           = $data['reference'] ?? null;

        // Credit what our wallet received after fees. Fall back to amount if
        // net_amount is absent for any reason.
        $credit = (float) ($data['net_amount'] ?? $data['amount'] ?? 0);

        if ($credit <= 0 || ! $ref || (! $accountNumber && ! $email)) {
            Log::warning('VA webhook: incomplete payload', ['ref' => $ref, 'data' => $data]);
            return;
        }

        DB::transaction(function () use ($accountNumber, $email, $credit, $ref) {
            // Locate the customer by the account the money landed in, else email.
            $q = DB::table('users');
            $accountNumber ? $q->where('va_account_number', $accountNumber)
                           : $q->where('email', $email);
            $user = $q->lockForUpdate()->first();

            if (! $user) {
                Log::warning('VA webhook: no matching user', compact('accountNumber', 'email', 'ref'));
                return;
            }

            // Idempotency: events are at-least-once, so dedupe on the transfer
            // reference — a retry finds it already recorded and stops.
            if (DB::table('wallet_transactions')->where('gateway_ref', $ref)->exists()) {
                return;
            }

            $after = round((float) $user->wallet_balance + $credit, 2);
            DB::table('users')->where('id', $user->id)->update(['wallet_balance' => $after]);

            DB::table('wallet_transactions')->insert([
                'user_id'       => $user->id,
                'reference'     => 'VA' . strtoupper(\Illuminate\Support\Str::random(9)),
                'gateway_ref'   => $ref,
                'type'          => 'topup',
                'status'        => 'settled',
                'amount'        => $credit,
                'balance_after' => $after,
                'currency'      => (string) ($data['currency'] ?? $this->setting('numbers.currency.code', 'NGN')),
                'note'          => 'Bank transfer (virtual account)',
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            // Referral commission on the funded amount, same as any top-up.
            $this->creditReferralCommission($user->id, $credit);
        });
    }

    /**
     * Verify a reference with the gateway and credit the wallet exactly once.
     *
     * Idempotent: the pending row is claimed inside a locked transaction, so a
     * webhook and a browser callback arriving together can't both credit.
     */
    private function settle(string $reference, ?int $userId = null): array
    {
        $pending = DB::table('wallet_transactions')
            ->where(function ($q) use ($reference) {
                $q->where('gateway_ref', $reference)
                  ->orWhere('reference', $reference);
            })
            ->where('type', 'topup')
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->orderByDesc('id')
            ->first();

        if (! $pending) {
            return ['ok' => false, 'message' => __('We could not find that payment.')];
        }

        if ($pending->status === 'settled') {
            return ['ok' => true, 'message' => __('This payment was already added to your wallet.')];
        }

        // Authoritative check — ask the gateway, never trust the redirect.
        $response = Http::withToken((string) \App\Support\Gateway::webblissSecret())
            ->acceptJson()
            ->get(rtrim((string) \App\Support\Gateway::webblissBase(), '/') . '/checkout/verify/' . urlencode($reference));

        $body = $response->json();
        $tx   = $body['data'] ?? [];

        $paid   = (bool) ($tx['paid'] ?? false);
        $amount = (float) ($tx['amount'] ?? 0);

        if (! $response->successful() || ! ($body['status'] ?? false) || ! $paid) {
            return ['ok' => false, 'message' => __('Payment not confirmed yet. If you were charged, it will reflect shortly.')];
        }

        // The amount the gateway confirms must match what we asked for.
        if (round($amount, 2) !== round((float) $pending->amount, 2)) {
            Log::warning('WebBlissPay amount mismatch', [
                'ref' => $reference, 'expected' => $pending->amount, 'got' => $amount,
            ]);
            return ['ok' => false, 'message' => __('Payment amount did not match. Contact support.')];
        }

        // ── Credit once, under a row lock ────────────────────────────
        $credited = DB::transaction(function () use ($pending, $amount) {
            // Re-read the pending row locked; bail if another process settled it.
            $row = DB::table('wallet_transactions')
                ->where('id', $pending->id)
                ->lockForUpdate()
                ->first();

            if (! $row || $row->status === 'settled') {
                return false;
            }

            $balance = (float) DB::table('users')
                ->where('id', $pending->user_id)
                ->lockForUpdate()
                ->value('wallet_balance');

            $after = round($balance + $amount, 2);

            DB::table('users')->where('id', $pending->user_id)->update(['wallet_balance' => $after]);

            DB::table('wallet_transactions')->where('id', $pending->id)->update([
                'status'        => 'settled',
                'balance_after' => $after,
                'updated_at'    => now(),
            ]);

            return true;
        });

        if (! $credited) {
            return ['ok' => true, 'message' => __('This payment was already added to your wallet.')];
        }

        // Optional: pay the referrer their commission on this deposit.
        $this->creditReferralCommission($pending->user_id, $amount);

        return ['ok' => true, 'message' => __('Wallet funded with :amt.', ['amt' => $this->money($amount)['formatted']])];
    }

    /** If this user was referred, credit their referrer's affiliate commission. */
    private function creditReferralCommission(int $userId, float $amount): void
    {
        // Only if the affiliate tables exist (feature may not be migrated).
        if (! DB::getSchemaBuilder()->hasTable('affiliate_conversions')) {
            return;
        }

        $referrer = DB::table('users')->where('id', $userId)->value('referred_by');
        if (! $referrer) {
            return;
        }

        // Flat 5% unless you wire tiers here later.
        $rate       = 5.0;
        $commission = round($amount * $rate / 100, 2);
        if ($commission <= 0) {
            return;
        }

        DB::transaction(function () use ($referrer, $userId, $amount, $commission) {
            DB::table('affiliate_conversions')->insert([
                'user_id'          => $referrer,
                'referred_user_id' => $userId,
                'type'             => 'deposit',
                'amount'           => $amount,
                'commission'       => $commission,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);

            DB::table('users')->where('id', $referrer)->increment('affiliate_earned', $commission);
        });
    }

    /**
     * Create (or return the existing) dedicated virtual bank account for the
     * signed-in customer. The customer transfers NGN to this fixed account and
     * their wallet is credited when the provider's webhook lands. Idempotent:
     * we store the account on the user, so this only calls the API once.
     */
    public function virtualAccount(Request $request)
    {
        $user = $request->user();

        // Already have one? Return it — no API call.
        if ($user->va_account_number) {
            return response()->json([
                'success' => true,
                'account' => [
                    'number' => $user->va_account_number,
                    'name'   => $user->va_account_name,
                    'bank'   => $user->va_bank_name,
                ],
            ]);
        }

        // Deposits must be on.
        if (! (bool) $this->setting('payment.deposits_enabled', true)) {
            return response()->json(['success' => false, 'message' => __('Deposits are temporarily unavailable.')], 422);
        }

        // The provider requires a valid 11-digit phone. Use the customer's, or
        // one they supply now via the modal. If neither is valid, tell the
        // client to prompt for it.
        $phone = preg_replace('/\D+/', '', (string) ($request->input('phone') ?: $user->phone));

        if (strlen($phone) !== 11) {
            return response()->json([
                'success'    => false,
                'need_phone' => true,
                'message'    => __('Please enter a valid 11-digit phone number to continue.'),
            ], 422);
        }

        // Save a newly-supplied phone so we don't ask again next time.
        if ($phone !== $user->phone) {
            DB::table('users')->where('id', $user->id)->update(['phone' => $phone, 'updated_at' => now()]);
        }

        $base  = rtrim((string) \App\Support\Gateway::webblissBase(), '/');
        $token = (string) \App\Support\Gateway::webblissSecret();

        // The VA endpoint is POST /api/v1/virtual-accounts on the DOMAIN ROOT.
        // The base already ends in /api/v1, so strip any /api/vN suffix to get
        // the root, then append the full path — avoids api/v1/api/v1/… doubling.
        $root = preg_replace('#/api/v\d+/?$#', '', $base);
        $vaUrl = $root . '/api/v1/virtual-accounts';

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout(20)
                ->post($vaUrl, [
                    'customer_name'  => $user->name,
                    'customer_email' => $user->email,
                    'customer_phone' => $phone,
                ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => __('Could not reach the account provider. Try again shortly.')], 502);
        }

        if (! $response->successful()) {
            $msg = $response->json('message') ?: __('Could not create a virtual account right now.');
            return response()->json(['success' => false, 'message' => $msg], 422);
        }

        $data = $response->json('data') ?? [];
        $number = $data['account_number'] ?? null;

        if (! $number) {
            return response()->json(['success' => false, 'message' => __('Invalid response from the account provider.')], 502);
        }

        // Persist so we never create a duplicate.
        DB::table('users')->where('id', $user->id)->update([
            'va_account_number' => $number,
            'va_account_name'   => $data['account_name'] ?? $user->name,
            'va_bank_name'      => $data['bank_name'] ?? null,
            'updated_at'        => now(),
        ]);

        return response()->json([
            'success' => true,
            'account' => [
                'number' => $number,
                'name'   => $data['account_name'] ?? $user->name,
                'bank'   => $data['bank_name'] ?? null,
            ],
        ]);
    }

    /**
     * PaymentPoint webhook — a second virtual-account provider. Different
     * payload and header from WebBlissPay, so it gets its own handler, but it
     * credits wallets through the same idempotent path.
     */
    public function paymentPointWebhook(Request $request)
    {
        $payload = $request->getContent();
        $secret  = (string) \App\Support\Gateway::paymentPointSecret();

        // PaymentPoint's header casing/name can vary; check the common forms.
        $signature = $request->header('Paymentpoint-Signature')
            ?? $request->header('paymentpoint-signature')
            ?? $request->header('X-Paymentpoint-Signature')
            ?? $request->header('http_paymentpoint_signature')
            ?? '';

        $expected = hash_hmac('sha256', $payload, $secret);

        // Also compute the base64 variant, in case they encode that way.
        $expectedB64 = base64_encode(hash_hmac('sha256', $payload, $secret, true));

        $match = $signature && (
            hash_equals($expected, $signature) ||
            hash_equals($expectedB64, $signature)
        );

        if (! $match) {
            // Log enough to diagnose WITHOUT leaking the secret.
            Log::warning('PaymentPoint webhook: bad signature', [
                'secret_set'      => $secret !== '',
                'secret_len'      => strlen($secret),
                'sig_received'    => $signature ?: '(none)',
                'sig_expected_hex'=> $expected,
                'all_headers'     => array_keys($request->headers->all()),
                'body_sample'     => mb_substr($payload, 0, 200),
            ]);
            return response()->json(['error' => 'invalid signature'], 400);
        }

        $data = $request->all();

        $ok = ($data['notification_status'] ?? null) === 'payment_successful'
            || ($data['transaction_status'] ?? null) === 'success';

        if ($ok) {
            $this->creditPaymentPoint($data);
        }

        return response()->json(['received' => true]);
    }

    /**
     * Credit a wallet from a PaymentPoint transfer. Matches the customer by the
     * customer_id we set when creating their account (falling back to the
     * receiver account number, then email), credits the SETTLEMENT amount (what
     * we actually receive after fees), and dedupes on transaction_id.
     */
    private function creditPaymentPoint(array $data): void
    {
        $txId    = $data['transaction_id'] ?? null;
        $custId  = $data['customer']['customer_id'] ?? null;
        $account = $data['receiver']['account_number'] ?? null;
        $email   = $data['customer']['email'] ?? null;

        // Settlement amount is what actually lands with us; fall back to paid.
        $credit = (float) ($data['settlement_amount'] ?? $data['amount_paid'] ?? 0);

        if ($credit <= 0 || ! $txId || (! $custId && ! $account && ! $email)) {
            Log::warning('PaymentPoint webhook: incomplete payload', ['tx' => $txId]);
            return;
        }

        DB::transaction(function () use ($txId, $custId, $account, $email, $credit) {
            // Prefer our own customer_id, then the VA account, then email.
            $q = DB::table('users');
            if ($custId && ctype_digit((string) $custId)) {
                $q->where('id', (int) $custId);
            } elseif ($account) {
                $q->where('va_account_number', $account);
            } else {
                $q->where('email', $email);
            }
            $user = $q->lockForUpdate()->first();

            if (! $user) {
                Log::warning('PaymentPoint webhook: no matching user', compact('custId', 'account', 'email', 'txId'));
                return;
            }

            // Idempotency: never credit the same transaction twice.
            if (DB::table('wallet_transactions')->where('gateway_ref', $txId)->exists()) {
                return;
            }

            $after = round((float) $user->wallet_balance + $credit, 2);
            DB::table('users')->where('id', $user->id)->update(['wallet_balance' => $after]);

            DB::table('wallet_transactions')->insert([
                'user_id'       => $user->id,
                'reference'     => 'PP' . strtoupper(\Illuminate\Support\Str::random(9)),
                'gateway_ref'   => $txId,
                'type'          => 'topup',
                'status'        => 'settled',
                'amount'        => $credit,
                'balance_after' => $after,
                'currency'      => (string) $this->setting('numbers.currency.code', 'NGN'),
                'note'          => 'Bank transfer (PaymentPoint)',
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            $this->creditReferralCommission($user->id, $credit);
        });
    }

    // ── Shared helpers ───────────────────────────────────────────────

    private ?array $settingsCache = null;

    private function setting(string $key, mixed $default = null): mixed
    {
        if ($this->settingsCache === null) {
            $this->settingsCache = Cache::remember('settings', 300, function () {
                try {
                    return DB::table('settings')->get(['key', 'value', 'type'])
                        ->mapWithKeys(fn ($r) => [$r->key => match ($r->type) {
                            'int'   => (int) $r->value,
                            'float' => (float) $r->value,
                            'bool'  => filter_var($r->value, FILTER_VALIDATE_BOOL),
                            default => $r->value,
                        }])->all();
                } catch (\Throwable) {
                    return [];
                }
            });
        }

        return $this->settingsCache[$key] ?? $default ?? config('services.' . $key);
    }

    private function money(float $amount): array
    {
        return [
            'amount'    => $amount,
            'formatted' => $this->setting('numbers.currency.symbol')
                . number_format($amount, (int) $this->setting('numbers.currency.decimals')),
        ];
    }
}