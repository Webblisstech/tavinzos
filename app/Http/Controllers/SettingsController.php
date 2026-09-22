<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * The user's own settings: profile details, password, and account actions.
 * Self-contained — no Breeze request classes — so it matches the rest of the
 * app and won't break if scaffolding is trimmed.
 */
class SettingsController extends Controller
{
    public function edit(Request $request)
    {
        return view('settings.index', [
            'user' => $request->user(),
        ]);
    }

    /** Name / email. */
    /**
     * Profile edits are admin-only now. Customers see a read-only profile and
     * this endpoint refuses changes even if the form is bypassed.
     */
    public function updateProfile(Request $request)
    {
        return back()->with('error', __('Name, email and phone can only be changed by support.'));
    }

    /** Password change — requires the current one. */
    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'confirmed', Password::defaults()],
        ]);

        DB::table('users')->where('id', $request->user()->id)->update([
            'password'   => Hash::make($data['password']),
            'updated_at' => now(),
        ]);

        return back()->with('status', __('Password changed.'));
    }

}