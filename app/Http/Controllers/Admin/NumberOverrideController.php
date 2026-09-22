<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\NumberOverrides;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Admin management of numbers-catalog rules: block a service, block a country,
 * or apply an extra price adjustment (global, per-service, or per-country).
 */
class NumberOverrideController extends Controller
{
    public function index(\App\Http\Controllers\NumberController $numbers)
    {
        $rows = DB::table('number_overrides')->orderByDesc('id')->get();

        // Which targets are currently blocked (lowercased for matching).
        $blockedSvc = $rows->where('type', 'block_service')->where('active', true)
            ->pluck('target')->map(fn ($t) => mb_strtolower(trim($t)))->all();
        $blockedCty = $rows->where('type', 'block_country')->where('active', true)
            ->pluck('target')->map(fn ($t) => mb_strtolower(trim($t)))->all();

        // Every service ENTRY (one per code), each tagged blocked or not. We
        // block by CODE because the same name repeats across pools/prices.
        $services = collect($numbers->catalogServiceNames())->map(fn ($s) => [
            'code'    => $s['code'],
            'name'    => $s['name'],
            'price'   => $s['price'],
            'blocked' => in_array(mb_strtolower(trim($s['code'])), $blockedSvc, true),
        ]);
        $countries = collect($numbers->catalogCountryNames())->map(fn ($name) => [
            'name'    => $name,
            'blocked' => in_array(mb_strtolower(trim($name)), $blockedCty, true),
        ]);

        return view('admin.numbers.index', [
            'services'   => $services,
            'countries'  => $countries,
            'countryList' => $numbers->catalogCountries(),
            'priceRules' => $rows->whereIn('type', ['price_service', 'price_country', 'price_all']),
        ]);
    }

    /** JSON: services for a country (drill-down step 2). */
    public function countryServices(Request $request, \App\Http\Controllers\NumberController $numbers, int $country)
    {
        return response()->json(['services' => $numbers->catalogCountryServices($country)]);
    }

    /** JSON: price tiers for a country+service, each flagged blocked. */
    public function serviceTiers(Request $request, \App\Http\Controllers\NumberController $numbers, int $country, string $service)
    {
        $tiers = $numbers->catalogTiers($country, $service);

        // A tier is blocked if a block_tier rule matches country:service:route.
        $blocked = DB::table('number_overrides')
            ->where('type', 'block_tier')->where('active', true)
            ->pluck('target')->map(fn ($t) => mb_strtolower($t))->all();

        $tiers = collect($tiers)->map(function ($t) use ($blocked, $country, $service) {
            $key = mb_strtolower($country . ':' . $service . ':' . ($t['route'] ?? $t['index']));
            $t['key']     = $key;
            $t['blocked'] = in_array($key, $blocked, true);
            return $t;
        })->all();

        return response()->json(['tiers' => $tiers]);
    }

    /** Toggle a single price tier on/off by its country:service:route key. */
    public function toggleTier(Request $request)
    {
        $data = $request->validate(['target' => ['required', 'string', 'max:255']]);

        $existing = DB::table('number_overrides')
            ->where('type', 'block_tier')
            ->whereRaw('LOWER(target) = ?', [mb_strtolower($data['target'])])
            ->first();

        if ($existing) {
            DB::table('number_overrides')->where('id', $existing->id)->delete();
            $msg = 'enabled';
        } else {
            DB::table('number_overrides')->insert([
                'type' => 'block_tier', 'target' => $data['target'],
                'active' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
            $msg = 'disabled';
        }

        NumberOverrides::flush();

        return response()->json(['ok' => true, 'state' => $msg]);
    }

    /** Toggle a service/country block by name — creates or removes the rule. */
    public function toggleBlock(Request $request)
    {
        $data = $request->validate([
            'type'   => ['required', 'in:block_service,block_country'],
            'target' => ['required', 'string', 'max:255'],
        ]);

        $existing = DB::table('number_overrides')
            ->where('type', $data['type'])
            ->whereRaw('LOWER(target) = ?', [mb_strtolower(trim($data['target']))])
            ->first();

        if ($existing) {
            // Remove the block (un-hide it).
            DB::table('number_overrides')->where('id', $existing->id)->delete();
            $msg = __(':t is now visible.', ['t' => $data['target']]);
        } else {
            DB::table('number_overrides')->insert([
                'type'       => $data['type'],
                'target'     => $data['target'],
                'active'     => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $msg = __(':t is now hidden.', ['t' => $data['target']]);
        }

        NumberOverrides::flush();

        return back()->with('status', $msg);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type'        => ['required', 'in:block_service,block_country,price_service,price_country,price_all'],
            'target'      => ['nullable', 'string', 'max:255'],
            'price_mode'  => ['nullable', 'in:percent,flat'],
            'price_value' => ['nullable', 'numeric'],
        ]);

        // Block rules need a target; a global price rule (price_all) does not.
        if (in_array($data['type'], ['block_service', 'block_country', 'price_service', 'price_country'], true)
            && empty($data['target'])) {
            return back()->with('error', __('This rule needs a target (service or country).'));
        }
        if (str_starts_with($data['type'], 'price') && ($data['price_mode'] === null || $data['price_value'] === null)) {
            return back()->with('error', __('A price rule needs a mode and value.'));
        }

        DB::table('number_overrides')->insert([
            'type'        => $data['type'],
            'target'      => $data['target'] ?? null,
            'price_mode'  => $data['price_mode'] ?? null,
            'price_value' => $data['price_value'] ?? null,
            'active'      => true,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        NumberOverrides::flush();

        return back()->with('status', __('Rule added.'));
    }

    public function toggle(Request $request, int $id)
    {
        $row = DB::table('number_overrides')->where('id', $id)->first();
        abort_unless($row, 404);

        DB::table('number_overrides')->where('id', $id)->update([
            'active'     => ! $row->active,
            'updated_at' => now(),
        ]);

        NumberOverrides::flush();

        return back()->with('status', __('Rule updated.'));
    }

    public function destroy(Request $request, int $id)
    {
        DB::table('number_overrides')->where('id', $id)->delete();
        NumberOverrides::flush();

        return back()->with('status', __('Rule removed.'));
    }
}