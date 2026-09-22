<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Admin control panel for the settings table. The SCHEMA below is the single
 * source of truth: it defines every editable setting, its type, group and how
 * it renders. Values are read from and written to the settings table, and the
 * settings cache is cleared on save so changes take effect at once.
 */
class AdminSettingsController extends Controller
{
    /**
     * key => [type, group, label, hint, input]
     * input: text | number | toggle | select:opt1,opt2
     */
    private const SCHEMA = [
        // ── Site ──────────────────────────────────────────────────────
        'site.name'                  => ['string', 'Site', 'Site name', 'Shown across the app and in emails.', 'text'],
        'site.tutorial_url'          => ['string', 'Site', 'Buy-page video (embed URL)', 'YouTube/Vimeo EMBED url shown on the numbers page.', 'text'],
        'site.deposit_tutorial_url'  => ['string', 'Site', 'Deposit video (embed URL)', 'EMBED url shown on the wallet page.', 'text'],

        // ── Currency ──────────────────────────────────────────────────
        'numbers.currency.symbol'    => ['string', 'Currency', 'Currency symbol', 'e.g. ₦, $, £.', 'text'],
        'numbers.currency.code'      => ['string', 'Currency', 'Currency code', 'e.g. NGN, USD.', 'text'],
        'numbers.currency.decimals'  => ['int',    'Currency', 'Decimal places', '0 for whole naira, 2 for cents.', 'number'],
        'numbers.currency.usd_rate'  => ['float',  'Currency', 'USD → local rate', 'How many local units per 1 USD of upstream cost.', 'number'],

        // ── Pricing ───────────────────────────────────────────────────
        'numbers.markup.mode'        => ['string', 'Pricing', 'Markup mode', 'How your margin is added to upstream cost.', 'select:percent,flat'],
        'numbers.markup.value'       => ['float',  'Pricing', 'Markup value', 'Percent (e.g. 35) or a flat amount, per mode.', 'number'],

        // ── Wallet ────────────────────────────────────────────────────
        'wallet.min_topup'           => ['float',  'Wallet', 'Minimum top-up', 'Smallest amount a customer can add.', 'number'],
        'wallet.max_topup'           => ['float',  'Wallet', 'Maximum top-up', 'Largest amount in one top-up.', 'number'],
        'numbers.min_wallet_balance' => ['float',  'Wallet', 'Minimum balance to buy', 'Balance required before a purchase is allowed.', 'number'],

        // ── Numbers ───────────────────────────────────────────────────
        'numbers.cancel_lock'        => ['int',    'Numbers', 'Cancel lock (seconds)', 'How long before a waiting number can be cancelled.', 'number'],
        'numbers.max_active_per_user'=> ['int',    'Numbers', 'Max active per user', 'How many waiting numbers a customer may hold.', 'number'],
        'numbers.usa.enabled'        => ['bool',   'Numbers', 'USA pool enabled', 'Show the USA numbers tab.', 'toggle'],
        'numbers.global.enabled'     => ['bool',   'Numbers', 'Global pool enabled', 'Show the All-countries tab.', 'toggle'],

        // ── Payments ──────────────────────────────────────────────────
        'payment.gateway_name'       => ['string', 'Payments', 'Gateway name', 'Shown to customers on the wallet page.', 'text'],
        'payment.deposits_enabled'   => ['bool',   'Payments', 'Deposits enabled', 'Allow customers to add funds.', 'toggle'],
        'payment.method_card'        => ['bool',   'Payments', 'Card payments', 'Accept debit/credit cards.', 'toggle'],
        'payment.method_transfer'    => ['bool',   'Payments', 'Bank transfer', 'Accept bank transfers.', 'toggle'],
        'payment.method_ussd'        => ['bool',   'Payments', 'USSD', 'Accept USSD payments.', 'toggle'],
        'payment.fee_percent'        => ['float',  'Payments', 'Processing fee (%)', 'Optional fee added to top-ups. 0 for none.', 'number'],
        'payment.min_topup_note'     => ['string', 'Payments', 'Deposit note', 'A short line shown under the deposit form.', 'text'],

        // ── Theme (look & feel) ───────────────────────────────────────
        'theme.primary'              => ['string', 'Theme', 'Primary color', 'Main buttons, links and the brand mark. Hex, e.g. #D91F2C.', 'color'],
        'theme.accent'              => ['string', 'Theme', 'Secondary color', 'Secondary buttons and highlights. Hex.', 'color'],
        'theme.font'                => ['string', 'Theme', 'Font family', 'The main UI font.', 'select:Bricolage Grotesque,Inter,Poppins,Manrope,DM Sans,Sora,Outfit,Plus Jakarta Sans'],
        'theme.font_scale'          => ['string', 'Theme', 'Font size', 'Overall text size.', 'select:small,normal,large'],
        'theme.radius'              => ['string', 'Theme', 'Corner rounding', 'How round cards and buttons are.', 'select:tight,normal,round'],

        // ── Support ───────────────────────────────────────────────────
        'support.whatsapp'           => ['string', 'Support', 'WhatsApp number', 'Full number with country code, e.g. 2348012345678.', 'text'],
        'support.email'              => ['string', 'Support', 'Support email', 'Where customers can reach you.', 'text'],
        'support.telegram'           => ['string', 'Support', 'Telegram handle/link', 'e.g. @yoursupport or a t.me link.', 'text'],
        'support.hours'              => ['string', 'Support', 'Support hours', 'e.g. 24/7 or Mon–Fri, 9am–6pm.', 'text'],
        'support.message'            => ['string', 'Support', 'Support blurb', 'A short line shown on the contact page.', 'text'],

        // ── Referral ──────────────────────────────────────────────────
        'referral.enabled'           => ['bool',   'Referral', 'Referral program on', 'Pay commission when referred users spend.', 'toggle'],
        'referral.threshold'         => ['float',  'Referral', 'Spend threshold', 'How much a referred user must spend before their referrer earns.', 'number'],
        'referral.mode'              => ['string', 'Referral', 'Commission mode', 'Percent of the threshold, or a flat amount.', 'select:percent,flat'],
        'referral.value'             => ['float',  'Referral', 'Commission value', 'Percent (e.g. 2) or a flat amount, per mode.', 'number'],

        // ── Access ────────────────────────────────────────────────────
        'site.registration_open'     => ['bool',   'Access', 'Registration open', 'Allow new sign-ups.', 'toggle'],
        'site.maintenance'           => ['bool',   'Access', 'Maintenance mode', 'Show a maintenance notice to customers.', 'toggle'],
        'store.maintenance'          => ['bool',   'Access', 'Account store maintenance', 'Close just the account store — numbers stay open.', 'toggle'],
    ];

    public function edit()
    {
        $current = DB::table('settings')->pluck('value', 'key');

        // Group the schema for the tabbed view.
        $groups = [];
        foreach (self::SCHEMA as $key => [$type, $group, $label, $hint, $input]) {
            $groups[$group][] = [
                'key'   => $key,
                'type'  => $type,
                'label' => $label,
                'hint'  => $hint,
                'input' => $input,
                'value' => $this->cast($current[$key] ?? null, $type),
            ];
        }

        return view('admin.settings.index', [
            'groups'  => $groups,
            'gateway' => [
                'base'      => (string) config('services.webblisspay.base'),
                'has_secret'=> filled(config('services.webblisspay.secret')),
                'webhook'   => rtrim((string) config('app.url'), '/') . '/api/wallet/webhook',
            ],
        ]);
    }

    public function update(Request $request)
    {
        $now = now();

        foreach (self::SCHEMA as $key => [$type, $group, $label, $hint, $input]) {
            // Toggles submit "1" only when checked, so an absent key means off.
            $raw = $type === 'bool'
                ? ($request->boolean($this->field($key)) ? '1' : '0')
                : $request->input($this->field($key));

            // Skip untouched non-bool fields that came back null (not rendered).
            if ($type !== 'bool' && $raw === null) {
                continue;
            }

            $exists = DB::table('settings')->where('key', $key)->exists();

            if ($exists) {
                DB::table('settings')->where('key', $key)->update([
                    'value'      => (string) $raw,
                    'type'       => $type,
                    'group'      => $group,
                    'label'      => $label,
                    'hint'       => $hint,
                    'updated_at' => $now,
                ]);
            } else {
                DB::table('settings')->insert([
                    'key'        => $key,
                    'value'      => (string) $raw,
                    'type'       => $type,
                    'group'      => $group,
                    'label'      => $label,
                    'hint'       => $hint,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        // The whole app reads settings through a 5-minute cache — clear it so
        // the change is live immediately, not up to 5 minutes later.
        Cache::forget('settings');

        return back()->with('status', __('Settings saved.'));
    }

    /** Dots aren't valid in HTML name attributes cleanly; use underscores. */
    private function field(string $key): string
    {
        return str_replace('.', '__', $key);
    }

    private function cast(?string $value, string $type): mixed
    {
        return match ($type) {
            'int'   => (int) $value,
            'float' => (float) $value,
            'bool'  => filter_var($value, FILTER_VALIDATE_BOOL),
            default => (string) ($value ?? ''),
        };
    }
}