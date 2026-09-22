<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:40', 'alpha_dash', 'unique:users,username'],
            'email'    => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'phone'    => ['required', 'string', 'max:32', 'min:7'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'ref'      => ['nullable', 'string', 'max:12'],
        ]);

        // Resolve the referrer from the ref code (field or cookie). Codes are
        // stored uppercase, so normalise what the user typed.
        $refCode  = Str::upper(trim((string) ($request->input('ref') ?: $request->cookie('ref'))));
        $referrer = $refCode
            ? DB::table('users')->where('ref_code', $refCode)->value('id')
            : null;

        $user = User::create([
            'name'        => $request->name,
            'username'    => $request->username ?: null,
            'email'       => $request->email,
            'phone'       => $request->phone,
            'password'    => Hash::make($request->password),
            'referred_by' => $referrer,
            'ref_code'    => $this->makeRefCode(),
        ]);

        Auth::login($user);

        // Send a 6-digit verification code and send them to enter it.
        \App\Http\Controllers\Auth\EmailCodeController::sendCode($user);

        return redirect()->route('verification.code')->with('status', __('We\'ve emailed you a 6-digit code.'));
    }

    /** A unique short referral code for the new user. */
    private function makeRefCode(): string
    {
        do {
            $code = Str::upper(Str::random(6));
        } while (DB::table('users')->where('ref_code', $code)->exists());

        return $code;
    }
}