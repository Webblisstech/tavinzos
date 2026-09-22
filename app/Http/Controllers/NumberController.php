<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientFunds;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Virtual numbers — everything in one place: upstream calls, caching,
 * retail pricing and the customer-facing responses.
 *
 * The one rule this file exists to enforce: nothing upstream is ever passed
 * through. Every response is rebuilt from a whitelist, so a customer never
 * sees the supplier, the cost price, a route handle, an activation ID, or
 * upstream error wording.
 */
class NumberController extends Controller
{
    // ═════════════════════════ Pages ═════════════════════════

    public function index(Request $request)
    {
        // Admin can switch either pool off from settings.
        $usaEnabled    = (bool) $this->setting('numbers.usa.enabled', true);
        $globalEnabled = (bool) $this->setting('numbers.global.enabled', true);

        // If both are off, the page is effectively closed.
        abort_if(! $usaEnabled && ! $globalEnabled, 503, __('Number service is temporarily unavailable.'));

        // Requested tab, but never land on a disabled pool.
        $tab = $request->query('tab') === 'global' ? 'global' : 'usa';
        if ($tab === 'usa' && ! $usaEnabled)     $tab = 'global';
        if ($tab === 'global' && ! $globalEnabled) $tab = 'usa';

        // Only hit upstream for pools that are on.
        $catalogue = $usaEnabled ? $this->usaCatalogue() : ['ok' => true, 'data' => []];
        $countries = $globalEnabled ? $this->countries() : ['ok' => true, 'data' => []];

        return view('numbers.index', [
            'tab'          => $tab,
            'usaEnabled'   => $usaEnabled,
            'globalEnabled'=> $globalEnabled,
            'orders'       => $this->activeOrders($request),
            'balance'      => $this->moneyRaw($this->balance($request)),
            // Embed URL, not a watch URL — see the note in settings.sql
            'tutorial'     => (string) $this->setting('site.tutorial_url', ''),
            'usaServices'  => $catalogue['ok'] ? $this->presentCatalogue($catalogue['data']) : collect(),
            'usaError'     => $catalogue['ok'] ? null : $this->message($catalogue),
            'countries'    => $countries['ok'] ? $this->presentCountries($countries['data']) : collect(),
            'countryError' => $countries['ok'] ? null : $this->message($countries),
            'pollUsa'      => config('services.numbers.usa.poll'),
            'pollGlobal'   => config('services.numbers.global.poll'),
            'cancelLock'   => $this->setting('numbers.cancel_lock'),
        ]);
    }

    /** Everything still in flight, so a page reload never loses an order. */
    public function orders(Request $request): JsonResponse
    {
        return $this->ok($this->activeOrders($request));
    }

    /**
     * Orders worth showing: anything still waiting, plus recently finished
     * ones so a code stays on screen after it lands.
     */
    private function activeOrders(Request $request)
    {
        return DB::table('verifications')
            ->where('user_id', $request->user()->id)
            ->where(function ($q) {
                $q->where('status', 'waiting')
                  ->orWhere('updated_at', '>=', now()->subHours(2));
            })
            ->orderByDesc('id')
            ->limit(25)
            ->get()
            ->map(fn ($row) => $this->presentOrder($row))
            ->values();
    }

    /** The ONLY shape an order is sent out in. No cost, no activation id. */
    private function presentOrder(object $row): array
    {
        return [
            'ref'      => $row->reference,
            'mode'     => $row->gateway,
            'service'  => $row->service,
            'country'  => $row->country,
            'number'   => $row->phone_number,
            'status'   => ucfirst($row->status),
            'code'     => $row->code,
            'charged'  => [
                'amount'    => (float) $row->price,
                'formatted' => $this->format((float) $row->price),
            ],
            'refunded' => (bool) $row->refunded_at,
            // Epoch seconds, so the browser can run the 3-minute lock even
            // after a reload — client clocks drift, purchase time does not.
            'bought_at' => $row->purchased_at
                ? strtotime($row->purchased_at)
                : strtotime($row->created_at),
        ];
    }

    // ═══════════════════════ Catalogue ═══════════════════════

    /** USA services, retail-priced. */
    public function usaServices(Request $request): JsonResponse
    {
        $response = $this->usaCatalogue(fresh: $request->boolean('fresh'));

        return $response['ok']
            ? $this->ok($this->presentCatalogue($response['data'])->values())
            : $this->fail($response);
    }

    /** Services for one country. 'provider' is stripped. */
    public function globalServices(int $country): JsonResponse
    {
        $response = $this->call('global', 'get', '/services/' . $country);

        if (! $response['ok']) {
            return $this->fail($response);
        }

        $services = collect($response['data']['services'] ?? [])
            ->map(fn ($s) => ['code' => $s['code'], 'name' => $s['name']])
            ->values();

        return $this->ok($services);
    }

    /**
     * Price tiers as retail figures plus an opaque quote token. The real cost
     * and the route handle stay in the cache — neither crosses the wire.
     */
    public function globalPrices(Request $request): JsonResponse
    {
        $input = $request->validate([
            'country' => ['required', 'integer', 'min:0', 'max:199999'],
            'service' => ['required', 'string', 'max:64'],
        ]);

        $response = $this->call('global', 'post', '/prices', [
            'country' => $input['country'],
            'service' => $input['service'],
        ]);

        if (! $response['ok']) {
            return $this->fail($response);
        }

        // Block a disabled service server-side.
        if (\App\Support\NumberOverrides::serviceBlocked($input['service'], $input['service'])) {
            return $this->fail(['code' => 'INVALID_SERVICE', 'status' => 422]);
        }

        $tiers = collect($response['data']['tiers'] ?? [])
            // Hide individual tiers the admin has disabled.
            ->reject(fn ($tier, $index) => \App\Support\NumberOverrides::tierBlocked(
                (int) $input['country'], $input['service'], $tier['route'] ?? $index
            ))
            ->values()
            ->map(function ($tier, $index) use ($request, $input) {
                $token = Str::random(24);

                Cache::put($this->quoteKey($request, $token), [
                    'country' => $input['country'],
                    'service' => $input['service'],
                    'price'   => (float) $tier['price'],    // cost — server-side only
                    'route'   => $tier['route'] ?? null,
                ], config('services.numbers.cache.quote'));

                return [
                    'quote'     => $token,
                    'label'     => $index === 0 ? __('Best price') : __('Option :n', ['n' => $index + 1]),
                    'price'     => $this->money((float) $tier['price'], $input['service'], (string) $input['country']),
                    'available' => (int) ($tier['available'] ?? 0),
                ];
            })
            ->values();

        return $this->ok($tiers);
    }

    // ════════════════════════ Purchase ═══════════════════════

    /**
     * Buy a number against the customer's own wallet.
     *
     * Order of operations matters more than anything else in this file:
     *
     *   1. work out cost and price WITHOUT calling upstream
     *   2. debit the wallet inside a locked transaction
     *   3. only then buy the number
     *   4. if the purchase fails, reverse the debit
     *
     * Debiting first is what stops two simultaneous requests spending the same
     * balance twice. Buying first and debiting after would hand out free
     * numbers whenever the second step failed.
     */
    public function purchase(Request $request): JsonResponse
    {
        $request->validate(['mode' => ['required', Rule::in(['usa', 'global'])]]);

        $mode = $request->input('mode');

        // Enforce the admin pool toggles server-side — the UI hides a disabled
        // tab, but a crafted request must be refused too.
        $enabledKey = 'numbers.' . $mode . '.enabled';
        if (! (bool) $this->setting($enabledKey, true)) {
            return $this->fail(['code' => 'POOL_DISABLED', 'status' => 422]);
        }

        $intent = $mode === 'usa'
            ? $this->usaIntent($request)
            : $this->globalIntent($request);

        if (isset($intent['error'])) {
            return $this->fail($intent['error']);
        }

        $ceiling = (int) $this->setting('numbers.max_active_per_user', 10);

        if ($ceiling > 0 && $this->activeCount($request) >= $ceiling) {
            return response()->json([
                'success' => false,
                'message' => __('You already have :n numbers waiting. Use or cancel one first.', ['n' => $ceiling]),
            ], 422);
        }

        $cost  = (float) $intent['cost'];
        $price = $this->sell($cost);
        $hold  = 'HOLD' . strtoupper(Str::random(8));

        // ── 2. Take the money ────────────────────────────────────────
        try {
            $balanceAfter = $this->debit($request->user()->id, $price, $hold, $intent['name']);
        } catch (InsufficientFunds $e) {
            // The gap is the actionable number: it is what they need to add.
            return response()->json([
                'success' => false,
                'message' => __('Short by :gap. This costs :price and your balance is :have.', [
                    'gap'   => $this->format(max(0, $price - $e->balance)),
                    'price' => $this->format($price),
                    'have'  => $this->format($e->balance),
                ]),
                'code'    => 'INSUFFICIENT_FUNDS',
            ], 422);
        }

        // ── 3. Buy it ────────────────────────────────────────────────
        $response = $this->call($mode, 'post', '/purchase', $intent['payload']);

        // 3b. A quote upstream only lives five minutes. Rather than making
        //     that the customer's problem, fetch a fresh tier and buy again.
        //     They are still charged the price they agreed to; we only accept
        //     a replacement that costs us no more than the original.
        if (! $response['ok'] && $mode === 'global' && $this->quoteWentStale($response)) {
            $replacement = $this->requote($intent, $cost);

            if ($replacement) {
                $response = $this->call('global', 'post', '/purchase', $replacement['payload']);

                if ($response['ok']) {
                    $cost = $replacement['cost'];   // real margin, agreed price
                    Log::info('Re-quoted a lapsed price', ['user_id' => $request->user()->id]);
                }
            }
        }

        // ── 4. Put the money back if it did not work ─────────────────
        if (! $response['ok']) {
            $this->credit($request->user()->id, $price, 'reversal', null,
                'Purchase failed — hold ' . $hold . ' released');

            return $this->fail($response);
        }

        $data = $response['data'] ?? [];
        $ref  = $this->record($request, $mode, $data, $cost, $price, $intent);

        // The number exists now, so retire the quote: one token, one number.
        if (! empty($intent['token'])) {
            Cache::forget($this->quoteKey($request, $intent['token']));
        }

        // Tie the debit to the order now that the order exists.
        DB::table('wallet_transactions')
            ->where('reference', $hold)
            ->update([
                'verification_id' => DB::table('verifications')->where('reference', $ref)->value('id'),
                'updated_at'      => now(),
            ]);

        // Count this toward the buyer's spend — may pay their referrer.
        \App\Support\Referral::recordSpend($request->user()->id, $price);

        $row = DB::table('verifications')->where('reference', $ref)->first();

        return $this->ok(
            $this->presentOrder($row) + ['balance' => $this->moneyRaw($balanceAfter)],
            201
        );
    }

    /** Upstream codes that mean "that price is no longer valid". */
    private function quoteWentStale(array $response): bool
    {
        return in_array($response['code'] ?? null, [
            'INVALID_PRICE',
            'PRICE_VERIFICATION_FAILED',
            'NO_NUMBERS_AVAILABLE',
        ], true) || ($response['status'] ?? 0) === 422;
    }

    /**
     * Fetches live tiers and returns one that costs no more than the quote.
     *
     * The ceiling is the point: prices drift both ways, and silently buying a
     * dearer tier would eat the margin the customer's price was based on. If
     * nothing fits, we give up and the purchase fails honestly.
     */
    private function requote(array $intent, float $ceiling): ?array
    {
        $payload = $intent['payload'];

        $prices = $this->call('global', 'post', '/prices', [
            'country' => $payload['country'],
            'service' => $payload['service'],
        ]);

        if (! $prices['ok']) {
            return null;
        }

        $tier = collect($prices['data']['tiers'] ?? [])
            ->filter(fn ($t) => (float) $t['price'] <= $ceiling + 0.0001)
            ->sortByDesc('price')     // closest to what we quoted
            ->first();

        if (! $tier) {
            return null;
        }

        return [
            'cost'    => (float) $tier['price'],
            'payload' => array_filter([
                'country'      => $payload['country'],
                'service'      => $payload['service'],
                'price'        => $tier['price'],
                'route'        => $tier['route'] ?? null,
                'service_name' => $payload['service_name'] ?? null,
            ], fn ($v) => $v !== null),
        ];
    }

    /**
     * What we intend to buy, priced, before any money or network moves.
     *
     * @return array{cost: float, name: ?string, code: ?string, payload: array}|array{error: array}
     */
    private function usaIntent(Request $request): array
    {
        $input = $request->validate(['code' => ['required', 'string', 'max:64']]);

        // Cost comes from our own cached catalogue, never from the request body.
        $service = collect($this->usaCatalogue()['data'] ?? [])
            ->firstWhere('code', $input['code']);

        if (! $service) {
            return ['error' => $this->err('INVALID_SERVICE', 422)];
        }

        return [
            'cost'    => (float) $service['price'],
            'name'    => $service['name'] ?? null,
            'code'    => $input['code'],
            'payload' => [
                'country'  => 'USA',
                'app'      => $input['code'],
                'app_name' => $service['name'] ?? null,
            ],
        ];
    }

    private function globalIntent(Request $request): array
    {
        $input = $request->validate([
            'quote'        => ['required', 'string', 'size:24'],
            'service_name' => ['nullable', 'string', 'max:64'],
        ]);

        // READ, don't consume. Pulling here meant a purchase that failed for
        // any reason — short balance, upstream hiccup — destroyed the quote,
        // so the customer's very next click died with "quote expired". The
        // token is only forgotten once a number has actually been issued.
        $quote = Cache::get($this->quoteKey($request, $input['quote']));

        if (! $quote) {
            return ['error' => $this->err('QUOTE_EXPIRED', 422)];
        }

        return [
            'cost'    => (float) $quote['price'],
            'name'    => $input['service_name'] ?? null,
            'code'    => $quote['service'],
            'token'   => $input['quote'],
            'payload' => array_filter([
                'country'      => $quote['country'],
                'service'      => $quote['service'],
                'price'        => $quote['price'],   // exactly as quoted
                'route'        => $quote['route'],
                'service_name' => $input['service_name'] ?? null,
            ], fn ($v) => $v !== null),
        ];
    }

    // ═══════════════════════ The wallet ══════════════════════

    /**
     * Takes money out. Returns the new balance.
     *
     * The row is locked for the length of the transaction, so a customer
     * firing two purchases at once cannot spend the same naira twice — the
     * second waits, re-reads the balance, and is refused if it is short.
     *
     * @throws InsufficientFunds
     */
    private function debit(int $userId, float $amount, string $reference, ?string $note = null): float
    {
        return DB::transaction(function () use ($userId, $amount, $reference, $note) {
            $balance = (float) DB::table('users')
                ->where('id', $userId)
                ->lockForUpdate()
                ->value('wallet_balance');

            $floor = (float) $this->setting('numbers.min_wallet_balance', 0);

            if ($balance - $amount < $floor) {
                throw new InsufficientFunds($balance);
            }

            $after = round($balance - $amount, 2);

            DB::table('users')->where('id', $userId)->update(['wallet_balance' => $after]);

            DB::table('wallet_transactions')->insert([
                'user_id'       => $userId,
                'reference'     => $reference,
                'type'          => 'purchase',
                'amount'        => -$amount,
                'balance_after' => $after,
                'currency'      => (string) $this->setting('numbers.currency.code', 'NGN'),
                'note'          => $note,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            return $after;
        });
    }

    /** Puts money back. Used for refunds and for reversing a failed purchase. */
    private function credit(int $userId, float $amount, string $type, ?int $verificationId = null, ?string $note = null): float
    {
        return DB::transaction(function () use ($userId, $amount, $type, $verificationId, $note) {
            $balance = (float) DB::table('users')
                ->where('id', $userId)
                ->lockForUpdate()
                ->value('wallet_balance');

            $after = round($balance + $amount, 2);

            DB::table('users')->where('id', $userId)->update(['wallet_balance' => $after]);

            DB::table('wallet_transactions')->insert([
                'user_id'         => $userId,
                'verification_id' => $verificationId,
                'reference'       => ($type === 'refund' ? 'RFND' : 'RVSL') . strtoupper(Str::random(8)),
                'type'            => $type,
                'amount'          => $amount,
                'balance_after'   => $after,
                'currency'        => (string) $this->setting('numbers.currency.code', 'NGN'),
                'note'            => $note,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            return $after;
        });
    }

    private function balance(Request $request): float
    {
        return (float) DB::table('users')
            ->where('id', $request->user()->id)
            ->value('wallet_balance');
    }

    /** An amount already in the display currency, in the usual wire shape. */
    private function moneyRaw(float $amount): array
    {
        return ['amount' => $amount, 'formatted' => $this->format($amount)];
    }

    /**
     * Persists the order and returns our public reference.
     *
     * The number is bought and the wallet is already debited by the time we
     * get here, so a write failure must be loud: the activation id goes to the
     * log or the customer has paid for something nobody can find.
     */
    private function record(Request $request, string $mode, array $data, float $cost, float $price, array $intent): string
    {
        $ref = strtoupper(Str::random(10));

        try {
            DB::table('verifications')->insert([
                'user_id'       => $request->user()->id,
                'reference'     => $ref,
                'gateway'       => $mode,
                'activation_id' => (string) ($data['activation_id'] ?? ''),
                'country'       => $data['country'] ?? null,
                'service'       => $intent['name'] ?: ($data['service'] ?? null),
                'service_code'  => $intent['code'] ?? null,
                'phone_number'  => $data['phone_number'] ?? null,
                'status'        => 'waiting',
                'cost'          => $cost,
                'price'         => $price,
                'currency'      => (string) $this->setting('numbers.currency.code', 'NGN'),
                'purchased_at'  => now(),
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Verification row failed to save — number is orphaned', [
                'gateway'       => $mode,
                'activation_id' => $data['activation_id'] ?? null,
                'user_id'       => $request->user()->id,
                'error'         => $e->getMessage(),
            ]);

            throw $e;
        }

        return $ref;
    }

    private function activeCount(Request $request): int
    {
        return (int) DB::table('verifications')
            ->where('user_id', $request->user()->id)
            ->where('status', 'waiting')
            ->count();
    }

    // ═════════════════════ Check and cancel ══════════════════

    public function check(string $ref, Request $request): JsonResponse
    {
        $order = $this->resolveRef($request, $ref);

        // Once a code is stored it never changes, so stop asking upstream.
        if ($order->status === 'completed' && $order->code) {
            return $this->ok([
                'ref'    => $ref,
                'status' => 'Completed',
                'code'   => $order->code,
                'number' => $order->phone_number,
                'refund_claimable' => false,
            ]);
        }

        $response = $this->call($order->gateway, 'get', '/check/' . rawurlencode($order->activation_id));

        if (! $response['ok']) {
            return $this->fail($response);
        }

        $data   = $response['data'] ?? [];
        $status = $data['status'] ?? 'Waiting';

        $this->syncStatus($order, $status, $data['code'] ?? null);

        return $this->ok([
            'ref'    => $ref,
            'mode'   => $order->gateway,
            'status' => $status,
            'code'   => $data['code'] ?? null,
            'number' => $data['phone_number'] ?? $order->phone_number,
            // A poll never moves money: false means the refund is still claimable.
            'refund_claimable' => $status === 'Cancelled'
                && ($data['refunded'] ?? null) === false,
        ]);
    }

    /** Mirrors the upstream status onto our row. Writes only on change. */
    private function syncStatus(object $order, string $status, ?string $code): void
    {
        $next = match ($status) {
            'Completed' => 'completed',
            'Cancelled' => 'cancelled',
            default     => 'waiting',
        };

        if ($next === $order->status && ! ($code && ! $order->code)) {
            return;
        }

        DB::table('verifications')->where('id', $order->id)->update(array_filter([
            'status'           => $next,
            'code'             => $code ?: $order->code,
            'code_received_at' => $code && ! $order->code ? now() : null,
            'updated_at'       => now(),
        ], fn ($v) => $v !== null));
    }

    public function cancel(string $ref, Request $request): JsonResponse
    {
        $order = $this->resolveRef($request, $ref);

        // Already settled — don't let a double click refund twice.
        if ($order->refunded_at) {
            return $this->ok([
                'ref'     => $ref,
                'status'  => 'Cancelled',
                'outcome' => 'refunded',
                'refund'  => [
                    'amount'    => (float) $order->refund_amount,
                    'formatted' => $this->format((float) $order->refund_amount),
                ],
            ]);
        }

        $response = $this->call($order->gateway, 'post', '/cancel/' . rawurlencode($order->activation_id));

        // The code landed in the race window. That is a win, not a failure —
        // the customer keeps the code and the charge stands.
        if (($response['code'] ?? null) === 'CODE_RECEIVED') {
            $code = $response['data']['code'] ?? null;
            $this->syncStatus($order, 'Completed', $code);

            return $this->ok([
                'ref'     => $ref,
                'status'  => 'Completed',
                'code'    => $code,
                'outcome' => 'code_received',
            ]);
        }

        // Upstream already voided this order (it timed out, was cancelled on
        // their side, or they no longer know it). If we never received a code,
        // the customer paid for nothing — refund locally rather than trapping
        // their money behind an upstream error. Guard on `! $order->code` so a
        // delivered order can never be refunded this way.
        $goneUpstream = in_array($response['code'] ?? null, [
            'ALREADY_CANCELLED',
            'ORDER_CANCELLED',
            'ORDER_NOT_FOUND',
            'NOT_FOUND',
            'ORDER_EXPIRED',
            'EXPIRED',
        ], true);

        if (! $response['ok'] && ! ($goneUpstream && ! $order->code)) {
            return $this->fail($response);
        }

        // Note when we refund an order the upstream had already dropped, so the
        // rate of these is visible — a high rate points at a provider problem.
        if ($goneUpstream) {
            Log::info('Refunding upstream-voided order', [
                'ref'  => $order->reference,
                'code' => $response['code'] ?? null,
            ]);
        }

        $refund = (float) $order->price;

        // The upstream cancel has already succeeded, so the refund must not be
        // lost if our own write fails — both statements go in one transaction.
        $balanceAfter = DB::transaction(function () use ($order, $refund) {
            DB::table('verifications')->where('id', $order->id)->update([
                'status'        => 'cancelled',
                'refund_amount' => $refund,
                'refunded_at'   => now(),
                'updated_at'    => now(),
            ]);

            return $this->credit(
                $order->user_id,
                $refund,
                'refund',
                $order->id,
                'Refund for ' . $order->reference
            );
        });

        return $this->ok([
            'ref'     => $ref,
            'status'  => 'Cancelled',
            'outcome' => 'refunded',
            'refund'  => ['amount' => $refund, 'formatted' => $this->format($refund)],
            'balance' => $this->moneyRaw($balanceAfter),
        ]);
    }

    // ═════════════════════ Upstream plumbing ═════════════════

    private function client(string $gateway): PendingRequest
    {
        return Http::baseUrl(rtrim(config("services.numbers.{$gateway}.base"), '/'))
            ->withToken(config('services.numbers.key'))
            ->acceptJson()
            ->timeout(config('services.numbers.timeout'));
    }

    /**
     * Single entry point for every upstream call.
     *
     * Never auto-retried: a purchase that times out may still have gone
     * through, and a blind retry risks a second charge.
     *
     * @return array{ok: bool, status: int, data: mixed, message: ?string, code: ?string}
     */
    private function call(string $gateway, string $method, string $path, array $payload = []): array
    {
        try {
            $response = $method === 'get'
                ? $this->client($gateway)->get($path, $payload)
                : $this->client($gateway)->post($path, $payload);
        } catch (ConnectionException) {
            // The host is deliberately kept out of the log line.
            Log::warning('Number gateway unreachable', ['gateway' => $gateway, 'path' => $path]);

            return $this->err('TRANSPORT_ERROR', 0);
        }

        $body = $response->json();

        if (! is_array($body)) {
            Log::warning('Number gateway bad payload', [
                'gateway' => $gateway,
                'path'    => $path,
                'status'  => $response->status(),
            ]);

            return $this->err('TRANSPORT_ERROR', 0);
        }

        $result = [
            'ok'      => (bool) ($body['success'] ?? false),
            'status'  => $response->status(),
            'data'    => $body['data'] ?? null,
            'message' => $body['message'] ?? $body['error'] ?? null,
            'code'    => $body['code'] ?? null,
        ];

        if (! $result['ok']) {
            Log::info('Number gateway error', [
                'gateway' => $gateway,
                'path'    => $path,
                'status'  => $result['status'],
                'code'    => $result['code'],
            ]);
        }

        return $result;
    }

    private function err(string $code, int $status): array
    {
        return ['ok' => false, 'status' => $status, 'data' => null, 'message' => null, 'code' => $code];
    }

    /**
     * Cached so purchase() can read cost without trusting the browser.
     *
     * A second, long-lived copy is kept as a fallback. Upstream hiccups are
     * routine — rate limits, brief outages — and a four-minute cache expiring
     * during one should not blank the whole tab. Prices go slightly stale
     * instead, and the purchase itself still validates server-side.
     */
    /**
     * Every service ENTRY in the USA pool — one row per code, not collapsed by
     * name. The upstream lists e.g. four "WhatsApp" codes at different prices;
     * the admin sees all four so they can hide a specific one. Blocking is by
     * code, since names repeat.
     */
    public function catalogServiceNames(): array
    {
        $usa = $this->usaCatalogue();
        if (! ($usa['ok'] ?? false)) {
            return [];
        }

        return collect($usa['data'] ?? [])
            ->map(fn ($s) => [
                'code'  => (string) ($s['code'] ?? ''),
                'name'  => (string) ($s['name'] ?? ''),
                'price' => (float) ($s['price'] ?? 0),
            ])
            ->filter(fn ($s) => $s['code'] !== '')
            ->sortBy(fn ($s) => [mb_strtolower($s['name']), $s['price']])
            ->values()
            ->all();
    }

    /**
     * Distinct country names in the global pool — for the admin catalog page.
     */
    public function catalogCountryNames(): array
    {
        $c = $this->countries();
        if (! ($c['ok'] ?? false)) {
            return [];
        }
        return collect($c['data']['countries'] ?? [])
            ->pluck('name')->filter()->unique()->sort()->values()->all();
    }

    /** Countries as [id, name] pairs — for the admin drill-down selector. */
    public function catalogCountries(): array
    {
        $c = $this->countries();
        if (! ($c['ok'] ?? false)) {
            return [];
        }
        return collect($c['data']['countries'] ?? [])
            ->map(fn ($x) => ['id' => $x['id'] ?? null, 'name' => $x['name'] ?? ''])
            ->filter(fn ($x) => $x['id'] !== null && $x['name'] !== '')
            ->sortBy('name')->values()->all();
    }

    /** Services available for one country — [code, name] — for the drill-down. */
    public function catalogCountryServices(int $country): array
    {
        $r = $this->call('global', 'get', '/services/' . $country);
        if (! ($r['ok'] ?? false)) {
            return [];
        }
        return collect($r['data']['services'] ?? [])
            ->map(fn ($s) => ['code' => (string) ($s['code'] ?? ''), 'name' => (string) ($s['name'] ?? '')])
            ->filter(fn ($s) => $s['code'] !== '')
            ->sortBy('name')->values()->all();
    }

    /** Raw price tiers for a country+service — cost, available — for admin view. */
    public function catalogTiers(int $country, string $service): array
    {
        $r = $this->call('global', 'post', '/prices', ['country' => $country, 'service' => $service]);
        if (! ($r['ok'] ?? false)) {
            return [];
        }
        return collect($r['data']['tiers'] ?? [])
            ->map(fn ($t, $i) => [
                'index'     => $i,
                'cost'      => (float) ($t['price'] ?? 0),
                'price'     => $this->money((float) ($t['price'] ?? 0), $service, (string) $country)['formatted'],
                'available' => (int) ($t['available'] ?? 0),
                'route'     => $t['route'] ?? null,
            ])
            ->values()->all();
    }

    private function usaCatalogue(bool $fresh = false): array
    {
        $key  = 'numbers:usa:catalogue';
        $safe = $key . ':last';

        if ($fresh) {
            Cache::forget($key);
        }

        if (($cached = Cache::get($key)) !== null) {
            return ['ok' => true, 'status' => 200, 'data' => $cached, 'message' => null, 'code' => null];
        }

        $response = $this->call('usa', 'get', '/apps/USA');

        if ($response['ok']) {
            Cache::put($key, $response['data'], config('services.numbers.cache.services'));
            Cache::put($safe, $response['data'], now()->addDay());

            return $response;
        }

        if (($stale = Cache::get($safe)) !== null) {
            Log::info('Serving stale USA catalogue', ['code' => $response['code']]);

            return ['ok' => true, 'status' => 200, 'data' => $stale, 'message' => null, 'code' => 'STALE'];
        }

        return $response;
    }

    /** Same fallback as the catalogue: the country list barely changes. */
    private function countries(bool $fresh = false): array
    {
        $key  = 'numbers:global:countries';
        $safe = $key . ':last';

        if ($fresh) {
            Cache::forget($key);
        }

        if (($cached = Cache::get($key)) !== null) {
            return ['ok' => true, 'status' => 200, 'data' => $cached, 'message' => null, 'code' => null];
        }

        $response = $this->call('global', 'get', '/countries');

        if ($response['ok']) {
            Cache::put($key, $response['data'], config('services.numbers.cache.countries'));
            Cache::put($safe, $response['data'], now()->addWeek());

            return $response;
        }

        if (($stale = Cache::get($safe)) !== null) {
            return ['ok' => true, 'status' => 200, 'data' => $stale, 'message' => null, 'code' => 'STALE'];
        }

        return $response;
    }

    // ══════════════════════ Retail pricing ═══════════════════

    /** Memoized for the request — Cache::remember still hits the driver every call. */
    private ?array $settingsCache = null;

    /**
     * A setting from the `settings` table, falling back to config.
     *
     * With thousands of services on the page this is called constantly, so the
     * whole table is loaded once per request and cached for five minutes.
     * Credentials stay in .env and are never read from here.
     */
    private function setting(string $key, mixed $default = null): mixed
    {
        if ($this->settingsCache === null) {
            $this->settingsCache = Cache::remember('settings', 300, function () {
                try {
                    return DB::table('settings')
                        ->get(['key', 'value', 'type'])
                        ->mapWithKeys(fn ($row) => [$row->key => match ($row->type) {
                            'int'   => (int) $row->value,
                            'float' => (float) $row->value,
                            'bool'  => filter_var($row->value, FILTER_VALIDATE_BOOL),
                            default => $row->value,
                        }])
                        ->all();
                } catch (\Throwable) {
                    // Table not migrated yet, or the DB is down — fall through to
                    // config rather than taking the storefront offline over a
                    // markup value.
                    return [];
                }
            });
        }

        return $this->settingsCache[$key] ?? $default ?? config('services.' . $key);
    }

    /**
     * Upstream cost (USD) → the price a customer pays, in the display currency.
     *
     * Two modes, never combined:
     *   percent → margin scales with cost, so an expensive service earns more
     *   flat    → the same gain on every number, whatever it costs you
     */
    private function sell(float $cost, ?string $service = null, ?string $country = null): float
    {
        $local = $cost * (float) $this->setting('numbers.currency.usd_rate');
        $value = (float) $this->setting('numbers.markup.value');

        $price = $this->setting('numbers.markup.mode') === 'flat'
            ? $local + $value
            : $local * (1 + $value / 100);

        // Admin price rules stack on top of the base markup (global, per-service
        // or per-country).
        $price = \App\Support\NumberOverrides::adjustPrice($price, $service, $country);

        return round($price, (int) $this->setting('numbers.currency.decimals'));
    }

    /** The only shape a price is ever sent out in. */
    private function money(float $cost, ?string $service = null, ?string $country = null): array
    {
        $price = $this->sell($cost, $service, $country);

        return ['amount' => $price, 'formatted' => $this->format($price)];
    }

    /** Formats an amount that is ALREADY in the display currency. */
    private function format(float $amount): string
    {
        return $this->setting('numbers.currency.symbol')
            . number_format($amount, (int) $this->setting('numbers.currency.decimals'));
    }

    // ════════════════════════ Presenters ═════════════════════

    /**
     * The USA catalogue, one row per code, as the endpoint returns it.
     *
     * Upstream lists the same service several times — a dozen "Signal" codes
     * at different stock prices — and each is a genuinely different pool. They
     * stay separate here; only the price is converted to retail.
     */
    private function presentCatalogue(mixed $catalogue)
    {
        return collect($catalogue ?? [])
            // Drop services the admin has blocked (by name or code).
            ->reject(fn ($s) => \App\Support\NumberOverrides::serviceBlocked($s['name'] ?? null, $s['code'] ?? null))
            ->map(fn ($s) => [
                'code'  => $s['code'],
                'name'  => $s['name'],
                'price' => $this->money((float) $s['price'], $s['name'] ?? null),
            ])
            // Case-insensitive, or "WhatsApp" sorts miles from "whatsapp".
            // Cheapest first within a name.
            ->sortBy(fn ($s) => [mb_strtolower($s['name']), $s['price']['amount']])
            ->values();
    }

    /**
     * Country name → ISO 3166-1 alpha-2, for flags.
     *
     * Keyed on the exact names the gateway returns, which are not always the
     * official ones ("Czech", "Bosnia", "Salvador", "Papua"). A name that is
     * missing simply renders without a flag rather than the wrong one.
     */
    private const ISO = [
        'Afghanistan' => 'af', 'Albania' => 'al', 'Algeria' => 'dz', 'Angola' => 'ao',
        'Anguilla' => 'ai', 'Antigua and Barbuda' => 'ag', 'Argentina' => 'ar',
        'Armenia' => 'am', 'Aruba' => 'aw', 'Australia' => 'au', 'Austria' => 'at',
        'Azerbaijan' => 'az', 'Bahamas' => 'bs', 'Bahrain' => 'bh', 'Bangladesh' => 'bd',
        'Barbados' => 'bb', 'Belarus' => 'by', 'Belgium' => 'be', 'Belize' => 'bz',
        'Benin' => 'bj', 'Bermuda' => 'bm', 'Bhutan' => 'bt', 'Bolivia' => 'bo',
        'Bosnia' => 'ba', 'Botswana' => 'bw', 'Brazil' => 'br', 'Brunei' => 'bn',
        'Bulgaria' => 'bg', 'Burkina Faso' => 'bf', 'Burundi' => 'bi', 'Cambodia' => 'kh',
        'Cameroon' => 'cm', 'Canada' => 'ca', 'Cape Verde' => 'cv', 'Cayman Islands' => 'ky',
        'Central African Republic' => 'cf', 'Chad' => 'td', 'Chile' => 'cl', 'China' => 'cn',
        'Colombia' => 'co', 'Comoros' => 'km', 'Congo' => 'cg', 'Costa Rica' => 'cr',
        'Croatia' => 'hr', 'Cuba' => 'cu', 'Cyprus' => 'cy', 'Czech' => 'cz',
        'DR Congo' => 'cd', 'Denmark' => 'dk', 'Djibouti' => 'dj', 'Dominica' => 'dm',
        'Dominican Republic' => 'do', 'Ecuador' => 'ec', 'Egypt' => 'eg',
        'Equatorial Guinea' => 'gq', 'Eritrea' => 'er', 'Estonia' => 'ee',
        'Ethiopia' => 'et', 'Fiji' => 'fj', 'Finland' => 'fi', 'France' => 'fr',
        'French Guiana' => 'gf', 'Gabon' => 'ga', 'Gambia' => 'gm', 'Georgia' => 'ge',
        'Germany' => 'de', 'Ghana' => 'gh', 'Gibraltar' => 'gi', 'Greece' => 'gr',
        'Greenland' => 'gl', 'Grenada' => 'gd', 'Guadeloupe' => 'gp', 'Guatemala' => 'gt',
        'Guinea' => 'gn', 'Guinea-Bissau' => 'gw', 'Guyana' => 'gy', 'Haiti' => 'ht',
        'Honduras' => 'hn', 'Hong Kong' => 'hk', 'Hungary' => 'hu', 'Iceland' => 'is',
        'India' => 'in', 'Indonesia' => 'id', 'Iran' => 'ir', 'Iraq' => 'iq',
        'Ireland' => 'ie', 'Israel' => 'il', 'Italy' => 'it', 'Ivory Coast' => 'ci',
        'Jamaica' => 'jm', 'Japan' => 'jp', 'Jordan' => 'jo', 'Kazakhstan' => 'kz',
        'Kenya' => 'ke', 'Korea' => 'kr', 'Kosovo' => 'xk', 'Kuwait' => 'kw',
        'Kyrgyzstan' => 'kg', 'Laos' => 'la', 'Latvia' => 'lv', 'Lebanon' => 'lb',
        'Lesotho' => 'ls', 'Liberia' => 'lr', 'Libya' => 'ly', 'Liechtenstein' => 'li',
        'Lithuania' => 'lt', 'Luxembourg' => 'lu', 'Macao' => 'mo', 'Madagascar' => 'mg',
        'Malawi' => 'mw', 'Malaysia' => 'my', 'Maldives' => 'mv', 'Mali' => 'ml',
        'Malta' => 'mt', 'Martinique' => 'mq', 'Mauritania' => 'mr', 'Mauritius' => 'mu',
        'Mexico' => 'mx', 'Moldova' => 'md', 'Monaco' => 'mc', 'Mongolia' => 'mn',
        'Montenegro' => 'me', 'Montserrat' => 'ms', 'Morocco' => 'ma', 'Mozambique' => 'mz',
        'Myanmar' => 'mm', 'Namibia' => 'na', 'Nepal' => 'np', 'Netherlands' => 'nl',
        'New Caledonia' => 'nc', 'New Zealand' => 'nz', 'Nicaragua' => 'ni',
        'Niger' => 'ne', 'Nigeria' => 'ng', 'Niue' => 'nu', 'North Macedonia' => 'mk',
        'Norway' => 'no', 'Oman' => 'om', 'Pakistan' => 'pk', 'Palestine' => 'ps',
        'Panama' => 'pa', 'Papua' => 'pg', 'Paraguay' => 'py', 'Peru' => 'pe',
        'Philippines' => 'ph', 'Poland' => 'pl', 'Portugal' => 'pt', 'Puerto Rico' => 'pr',
        'Qatar' => 'qa', 'Reunion' => 're', 'Romania' => 'ro', 'Russian Federation' => 'ru',
        'Rwanda' => 'rw', 'Saint Kitts and Nevis' => 'kn', 'Saint Lucia' => 'lc',
        'Saint Vincent and the Grenadines' => 'vc', 'Salvador' => 'sv', 'Samoa' => 'ws',
        'Sao Tome and Principe' => 'st', 'Saudi Arabia' => 'sa', 'Senegal' => 'sn',
        'Serbia' => 'rs', 'Seychelles' => 'sc', 'Sierra Leone' => 'sl', 'Singapore' => 'sg',
        'Sint Maarten' => 'sx', 'Slovakia' => 'sk', 'Slovenia' => 'si', 'Somalia' => 'so',
        'South Africa' => 'za', 'South Sudan' => 'ss', 'Spain' => 'es', 'Sri Lanka' => 'lk',
        'Sudan' => 'sd', 'Suriname' => 'sr', 'Swaziland' => 'sz', 'Sweden' => 'se',
        'Switzerland' => 'ch', 'Syria' => 'sy', 'Taiwan' => 'tw', 'Tajikistan' => 'tj',
        'Tanzania' => 'tz', 'Thailand' => 'th', 'Timor-Leste' => 'tl', 'Togo' => 'tg',
        'Trinidad and Tobago' => 'tt', 'Tunisia' => 'tn', 'Turkey' => 'tr',
        'Turkmenistan' => 'tm', 'UAE' => 'ae', 'USA' => 'us', 'Uganda' => 'ug',
        'Ukraine' => 'ua', 'United Kingdom' => 'gb', 'United States (virtual)' => 'us',
        'Uruguay' => 'uy', 'Uzbekistan' => 'uz', 'Vanuatu' => 'vu', 'Venezuela' => 've',
        'Vietnam' => 'vn', 'Yemen' => 'ye', 'Zambia' => 'zm', 'Zimbabwe' => 'zw',
    ];

    private function presentCountries(mixed $payload)
    {
        // 'provider' is dropped: sv1 / sv2 mean nothing to a customer and
        // everything to a competitor.
        return collect($payload['countries'] ?? [])
            // Drop countries the admin has blocked.
            ->reject(fn ($c) => \App\Support\NumberOverrides::countryBlocked($c['name'] ?? null))
            ->map(fn ($c) => [
                'id'   => $c['id'],
                'name' => $c['name'],
                'iso'  => self::ISO[$c['name']] ?? null,
            ])
            ->sortBy('name')
            ->values();
    }

    // ═════════════════════ Tokens and refs ═══════════════════

    private function quoteKey(Request $request, string $token): string
    {
        return sprintf('numbers:quote:%d:%s', $request->user()->id, $token);
    }

    /**
     * The order row, scoped to the signed-in user.
     *
     * 404 rather than 403 — an unknown reference should not confirm that
     * someone else's order exists.
     */
    private function resolveRef(Request $request, string $ref): object
    {
        $order = DB::table('verifications')
            ->where('reference', $ref)
            ->where('user_id', $request->user()->id)
            ->first();

        abort_unless($order, 404);

        return $order;
    }

    // ═══════════════════════ Responses ═══════════════════════

    private function ok(mixed $data, int $status = 200): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $data], $status);
    }

    /** Upstream message text is never forwarded — it can name the supplier. */
    private function fail(array $response): JsonResponse
    {
        return response()->json([
            'success'     => false,
            'message'     => $this->message($response),
            'retry_after' => $this->secondsRemaining($response),
        ], $this->status($response));
    }

    /**
     * Customer-facing wording, always. Internal codes go to the log and
     * nowhere else — not even behind APP_DEBUG, because debug gets left on.
     */
    private function message(array $response): string
    {

        return match ($response['code'] ?? null) {
            'OUT_OF_STOCK'              => __('That service just sold out. Pick another or try again in a moment.'),
            'PROVIDER_DISABLED'         => __('That service is switched off right now.'),
            'POOL_DISABLED'             => __('This service is currently unavailable. Please try the other tab.'),
            'INVALID_SERVICE'           => __('That service is no longer available — refresh the list.'),
            'QUOTE_EXPIRED',
            'INVALID_PRICE',
            'PRICE_VERIFICATION_FAILED' => __('That price has expired. Check prices again.'),
            'INVALID_COUNTRY'           => __('That country is not supported.'),
            'NOT_FOUND', 'INVALID_ID'   => __('That order could not be found.'),
            'TOO_EARLY'                 => __('You can cancel in :seconds seconds.', [
                                               'seconds' => $this->secondsRemaining($response)
                                                   ?? config('services.numbers.cancel_lock'),
                                           ]),
            'CONCURRENT_ERROR'          => __('Two orders collided — nothing was charged. Try again.'),
            'IN_PROGRESS'               => __('A cancellation is already running. Try again in a second.'),
            'RATE_LIMITED'              => __('Too many requests. Wait a few seconds.'),
            // Our own funding, auth and account problems are not the
            // customer's business — they read as a plain outage.
            default                     => __('This service is temporarily unavailable. Please try again shortly.'),
        };
    }

    private function status(array $response): int
    {
        return match ($response['code'] ?? null) {
            'UNAUTHENTICATED', 'ACCOUNT_SUSPENDED', 'ACCOUNT_LOCKED',
            'INSUFFICIENT_BALANCE', 'TRANSPORT_ERROR',
            'PROVIDER_ERROR', 'SERVER_ERROR' => 503,
            'RATE_LIMITED'                   => 429,
            'NOT_FOUND', 'INVALID_ID'        => 404,
            default                          => 422,
        };
    }

    private function secondsRemaining(array $response): ?int
    {
        if (($response['code'] ?? null) !== 'TOO_EARLY' || empty($response['message'])) {
            return null;
        }

        return preg_match('/(\d+)\s*second/i', $response['message'], $m) ? (int) $m[1] : null;
    }
}