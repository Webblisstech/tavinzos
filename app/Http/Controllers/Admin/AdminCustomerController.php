<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Admin customer management: browse users, see everything about one, adjust
 * their wallet (fund/debit — locked and ledgered), and suspend/unsuspend.
 */
class AdminCustomerController extends Controller
{
    public function index(Request $request)
    {
        $q      = trim((string) $request->query('q'));
        $status = $request->query('status'); // active | suspended

        $rows = DB::table('users')
            ->when($q, fn ($x) => $x->where(function ($w) use ($q) {
                $w->where('name', 'like', "%$q%")
                  ->orWhere('email', 'like', "%$q%")
                  ->orWhere('phone', 'like', "%$q%")
                  ->orWhere('ref_code', 'like', "%$q%");
            }))
            ->when($status === 'active',    fn ($x) => $x->whereNull('suspended_at'))
            ->when($status === 'suspended', fn ($x) => $x->whereNotNull('suspended_at'))
            ->orderByDesc('wallet_balance')
            ->orderByDesc('id')
            ->limit(300)
            ->get(['id', 'name', 'email', 'phone', 'wallet_balance', 'total_spent', 'suspended_at', 'created_at'])
            ->map(fn ($u) => [
                'id'        => $u->id,
                'name'      => $u->name,
                'email'     => $u->email,
                'phone'     => $u->phone,
                'balance'   => $this->money((float) $u->wallet_balance),
                'spent'     => $this->money((float) $u->total_spent),
                'suspended' => (bool) $u->suspended_at,
                'joined'    => $u->created_at,
            ]);

        $stats = [
            'total'     => DB::table('users')->count(),
            'active'    => DB::table('users')->whereNull('suspended_at')->count(),
            'suspended' => DB::table('users')->whereNotNull('suspended_at')->count(),
            'held'      => $this->money((float) DB::table('users')->sum('wallet_balance')),
        ];

        return view('admin.customers.index', [
            'rows'    => $rows,
            'stats'   => $stats,
            'filters' => ['q' => $q, 'status' => $status],
        ]);
    }

    public function show(Request $request, int $id)
    {
        $u = DB::table('users')->where('id', $id)->first();
        abort_unless($u, 404);

        // Recent wallet activity.
        $ledger = DB::table('wallet_transactions')
            ->where('user_id', $id)->orderByDesc('id')->limit(20)->get()
            ->map(fn ($t) => [
                'type'    => $t->type,
                'amount'  => $this->money((float) $t->amount),
                'credit'  => (float) $t->amount >= 0,
                'note'    => $t->note,
                'at'      => $t->created_at,
            ]);

        // Order counts across both product lines.
        $numbers  = DB::table('verifications')->where('user_id', $id)->count();
        $accounts = DB::table('log_orders')->where('user_id', $id)->count();

        // Who referred them, and who they've referred.
        $referrer = $u->referred_by
            ? DB::table('users')->where('id', $u->referred_by)->value('email')
            : null;
        $referredCount = DB::table('users')->where('referred_by', $id)->count();

        return view('admin.customers.show', [
            'u' => [
                'id'         => $u->id,
                'name'       => $u->name,
                'email'      => $u->email,
                'phone'      => $u->phone ?: '—',
                'balance'    => $this->money((float) $u->wallet_balance),
                'spent'      => $this->money((float) $u->total_spent),
                'earned'     => $this->money((float) ($u->affiliate_earned ?? 0)),
                'ref_code'   => $u->ref_code,
                'referrer'   => $referrer,
                'referred'   => $referredCount,
                'suspended'  => (bool) $u->suspended_at,
                'suspended_reason' => $u->suspended_reason ?? null,
                'verified'   => (bool) $u->email_verified_at,
                'joined'     => $u->created_at,
                // Raw values for the full edit form.
                'raw_balance'     => (float) $u->wallet_balance,
                'raw_spent'       => (float) $u->total_spent,
                'raw_earned'      => (float) ($u->affiliate_earned ?? 0),
                'referred_by'     => $u->referred_by,
                'referral_paid'   => (bool) ($u->referral_paid ?? false),
                'va_number'       => $u->va_account_number,
                'va_name'         => $u->va_account_name,
                'va_bank'         => $u->va_bank_name,
            ],
            'ledger'   => $ledger,
            'numbers'  => $numbers,
            'accounts' => $accounts,
        ]);
    }

    /** Fund or debit a wallet — locked, ledgered, idempotent per submit. */
    public function adjust(Request $request, int $id)
    {
        $data = $request->validate([
            'direction' => ['required', 'in:credit,debit'],
            'amount'    => ['required', 'numeric', 'min:0.01', 'max:100000000'],
            'note'      => ['nullable', 'string', 'max:255'],
        ]);

        $result = DB::transaction(function () use ($id, $data) {
            $user = DB::table('users')->where('id', $id)->lockForUpdate()->first();
            if (! $user) {
                return ['ok' => false, 'msg' => __('Customer not found.')];
            }

            $amount  = round((float) $data['amount'], 2);
            $signed  = $data['direction'] === 'credit' ? $amount : -$amount;
            $balance = (float) $user->wallet_balance;

            if ($signed < 0 && $balance + $signed < 0) {
                return ['ok' => false, 'msg' => __('Debit exceeds the balance.')];
            }

            $after = round($balance + $signed, 2);
            DB::table('users')->where('id', $id)->update(['wallet_balance' => $after]);

            DB::table('wallet_transactions')->insert([
                'user_id'       => $id,
                'reference'     => 'ADJ' . strtoupper(Str::random(9)),
                'type'          => 'adjustment',
                'status'        => 'settled',
                'amount'        => $signed,
                'balance_after' => $after,
                'currency'      => (string) $this->setting('numbers.currency.code', 'NGN'),
                'note'          => 'Admin ' . $data['direction'] . ($data['note'] ? ' · ' . $data['note'] : ''),
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            return ['ok' => true, 'msg' => __(':dir of :amt applied.', [
                'dir' => ucfirst($data['direction']),
                'amt' => $this->money($amount)['formatted'],
            ])];
        });

        return back()->with($result['ok'] ? 'status' : 'error', $result['msg']);
    }

    /** Suspend or unsuspend an account. */
    public function suspend(Request $request, int $id)
    {
        $data = $request->validate([
            'action' => ['required', 'in:suspend,unsuspend'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $user = DB::table('users')->where('id', $id)->first();
        abort_unless($user, 404);

        if ($data['action'] === 'suspend') {
            DB::table('users')->where('id', $id)->update([
                'suspended_at'     => now(),
                'suspended_reason' => $data['reason'] ?? null,
                'updated_at'       => now(),
            ]);
            $msg = __('Account suspended.');
        } else {
            DB::table('users')->where('id', $id)->update([
                'suspended_at'     => null,
                'suspended_reason' => null,
                'updated_at'       => now(),
            ]);
            $msg = __('Account reinstated.');
        }

        return back()->with('status', $msg);
    }

    /** Edit every editable field on the user record. */
    public function updateProfile(Request $request, int $id)
    {
        $user = DB::table('users')->where('id', $id)->first();
        abort_unless($user, 404);

        $data = $request->validate([
            'name'              => ['required', 'string', 'max:255'],
            'email'             => ['required', 'email', 'max:255', 'unique:users,email,' . $id],
            'phone'             => ['nullable', 'string', 'max:32'],
            'verified'          => ['nullable', 'boolean'],
            'ref_code'          => ['nullable', 'string', 'max:12', 'unique:users,ref_code,' . $id],
            'referred_by'       => ['nullable', 'integer', 'exists:users,id'],
            'wallet_balance'    => ['nullable', 'numeric', 'min:0', 'max:1000000000'],
            'total_spent'       => ['nullable', 'numeric', 'min:0'],
            'affiliate_earned'  => ['nullable', 'numeric', 'min:0'],
            'referral_paid'     => ['nullable', 'boolean'],
            'va_account_number' => ['nullable', 'string', 'max:20'],
            'va_account_name'   => ['nullable', 'string', 'max:255'],
            'va_bank_name'      => ['nullable', 'string', 'max:60'],
        ]);

        // A user can't be their own referrer.
        $referredBy = $data['referred_by'] ?? null;
        if ((int) $referredBy === $id) {
            $referredBy = null;
        }

        // If the admin directly changed the wallet balance, write a ledger row
        // so the money trail stays complete — a silent balance edit is a red
        // flag in any audit.
        $newBalance = array_key_exists('wallet_balance', $data) && $data['wallet_balance'] !== null
            ? round((float) $data['wallet_balance'], 2)
            : (float) $user->wallet_balance;

        DB::transaction(function () use ($id, $data, $user, $request, $referredBy, $newBalance) {
            DB::table('users')->where('id', $id)->update([
                'name'              => $data['name'],
                'email'             => $data['email'],
                'phone'             => $data['phone'] ?? null,
                'email_verified_at' => $request->boolean('verified')
                    ? ($user->email_verified_at ?? now()) : null,
                'ref_code'          => $data['ref_code'] ?? $user->ref_code,
                'referred_by'       => $referredBy,
                'wallet_balance'    => $newBalance,
                'total_spent'       => $data['total_spent'] ?? $user->total_spent,
                'affiliate_earned'  => $data['affiliate_earned'] ?? $user->affiliate_earned,
                'referral_paid'     => $request->boolean('referral_paid'),
                'va_account_number' => $data['va_account_number'] ?? null,
                'va_account_name'   => $data['va_account_name'] ?? null,
                'va_bank_name'      => $data['va_bank_name'] ?? null,
                'updated_at'        => now(),
            ]);

            // Ledger the manual balance change, if any.
            $delta = round($newBalance - (float) $user->wallet_balance, 2);
            if (abs($delta) >= 0.01) {
                DB::table('wallet_transactions')->insert([
                    'user_id'       => $id,
                    'reference'     => 'ADJ' . strtoupper(Str::random(9)),
                    'type'          => 'adjustment',
                    'status'        => 'settled',
                    'amount'        => $delta,
                    'balance_after' => $newBalance,
                    'currency'      => (string) $this->setting('numbers.currency.code', 'NGN'),
                    'note'          => 'Admin balance edit',
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            }
        });

        return back()->with('status', __('Customer updated.'));
    }

    /** Sign in as this customer (impersonation). Admin can return afterwards. */
    public function loginAs(Request $request, int $id)
    {
        $target = \App\Models\User::find($id);
        abort_unless($target, 404);

        if ($target->suspended_at !== null) {
            return back()->with('error', __('Cannot sign in as a suspended customer. Reinstate them first.'));
        }

        // Remember who we really are so we can switch back.
        $request->session()->put('impersonator_admin_id', auth('admin')->id());

        // Log in on the web guard as the customer.
        auth('web')->loginUsingId($id);

        return redirect(route('dashboard'))->with('status', __('You are now viewing as :name.', ['name' => $target->name]));
    }

    /** Set a new password for the customer (admin-initiated reset). */
    public function resetPassword(Request $request, int $id)
    {
        abort_unless(DB::table('users')->where('id', $id)->exists(), 404);

        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        DB::table('users')->where('id', $id)->update([
            'password'   => \Illuminate\Support\Facades\Hash::make($data['password']),
            'updated_at' => now(),
        ]);

        return back()->with('status', __('Password reset. Share the new password with the customer securely.'));
    }

    // ── money helpers ────────────────────────────────────────────────
    private ?array $settingsCache = null;
    private function setting(string $key, mixed $default = null): mixed
    {
        if ($this->settingsCache === null) {
            $this->settingsCache = Cache::remember('settings', 300, function () {
                try {
                    return DB::table('settings')->get(['key', 'value', 'type'])
                        ->mapWithKeys(fn ($r) => [$r->key => match ($r->type) {
                            'int' => (int) $r->value, 'float' => (float) $r->value,
                            'bool' => filter_var($r->value, FILTER_VALIDATE_BOOL), default => $r->value,
                        }])->all();
                } catch (\Throwable) { return []; }
            });
        }
        return $this->settingsCache[$key] ?? $default ?? config('services.' . $key);
    }
    private function money(float $a): array
    {
        return ['amount' => $a, 'formatted' => $this->setting('numbers.currency.symbol')
            . number_format($a, (int) $this->setting('numbers.currency.decimals'))];
    }
}