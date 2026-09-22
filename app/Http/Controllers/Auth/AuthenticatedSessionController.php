<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * @throws ValidationException
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        // Suspended accounts can authenticate but must not get a session.
        if ($request->user()->suspended_at !== null) {
            $reason = $request->user()->suspended_reason;

            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'login' => $reason
                    ? __('Your account is suspended: :reason', ['reason' => $reason])
                    : __('Your account has been suspended. Please contact support.'),
            ]);
        }

        $request->session()->regenerate();

        // If their email isn't verified, send them to enter their code (the
        // verify page auto-sends a fresh one) rather than bouncing off the
        // dashboard via middleware.
        if ($request->user()->email_verified_at === null) {
            return redirect()->route('verification.code');
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}