<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The single "login" field accepts an email OR a username.
     */
    public function rules(): array
    {
        return [
            'login'    => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate by email or username.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $login    = (string) $this->input('login');
        $password = $this->input('password');
        $remember = $this->boolean('remember');

        // An email matches the email column. Otherwise try username first, then
        // fall back to name — so existing users can sign in with the name they
        // registered with, and newer users with their username.
        $isEmail = (bool) filter_var($login, FILTER_VALIDATE_EMAIL);

        $attempts = $isEmail
            ? [['email' => $login]]
            : [['username' => $login], ['name' => $login]];

        $ok = false;
        foreach ($attempts as $creds) {
            if (Auth::attempt($creds + ['password' => $password], $remember)) {
                $ok = true;
                break;
            }
        }

        if (! $ok) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'login' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'login' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower((string) $this->input('login')) . '|' . $this->ip());
    }
}