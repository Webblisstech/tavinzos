<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Contact-support page. All contact details come from the settings table so an
 * admin can change them without a deploy.
 */
class SupportController extends Controller
{
    public function index(Request $request)
    {
        return view('support.index', [
            'whatsapp' => (string) $this->setting('support.whatsapp', ''),
            'email'    => (string) $this->setting('support.email', ''),
            'telegram' => (string) $this->setting('support.telegram', ''),
            'hours'    => (string) $this->setting('support.hours', '24/7'),
            'message'  => (string) $this->setting('support.message', ''),
        ]);
    }

    private function setting(string $key, mixed $default = null): mixed
    {
        $all = Cache::remember('settings', 300, function () {
            try {
                return DB::table('settings')->get(['key', 'value', 'type'])
                    ->mapWithKeys(fn ($r) => [$r->key => match ($r->type) {
                        'int' => (int) $r->value, 'float' => (float) $r->value,
                        'bool' => filter_var($r->value, FILTER_VALIDATE_BOOL), default => $r->value,
                    }])->all();
            } catch (\Throwable) { return []; }
        });
        return $all[$key] ?? $default;
    }
}