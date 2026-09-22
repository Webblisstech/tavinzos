<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Admin login, on its own guard and its own throttle. Deliberately terse:
 * one message for any failure, so the form never reveals whether an email
 * exists.
 */
class LoginController extends Controller
{
    public function show()
    {
        return view('admin.auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Five tries a minute, keyed to email + IP.
        $key = 'admin-login:' . strtolower($data['email']) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Too many attempts. Try again in ' . RateLimiter::availableIn($key) . 's.',
            ]);
        }

        if (! Auth::guard('admin')->attempt($data, $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages([
                'email' => 'Those credentials do not match our records.',
            ]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        Auth::guard('admin')->user()->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('admin.logs.index'));
    }

    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}