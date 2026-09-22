<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Email verification by 6-digit code. On registration a code is generated and
 * emailed; the user types it on the verify page. No magic link.
 */
class EmailCodeController extends Controller
{
    /** Show the "enter your code" page. */
    public function show(Request $request)
    {
        $user = $request->user();

        if ($user->email_verified_at) {
            return redirect()->route('dashboard');
        }

        // Send a fresh code if they don't have a valid, unexpired one — so a
        // user logging in unverified (whose registration code has expired)
        // always has a code waiting, without having to click Resend.
        $row = DB::table('users')->where('id', $user->id)
            ->first(['email_code', 'email_code_expires_at']);

        $hasValidCode = $row && $row->email_code
            && $row->email_code_expires_at
            && now()->lessThan($row->email_code_expires_at);

        if (! $hasValidCode) {
            // Respect the resend limit so a refresh loop can't spam email.
            $key = 'resend-code:' . $user->id;
            if (! RateLimiter::tooManyAttempts($key, 3)) {
                RateLimiter::hit($key, 120);
                self::sendCode($user);
            }
        }

        return view('auth.verify-code');
    }

    /** Generate a code, store it, and email it. Called on register + resend. */
    public static function sendCode($user): void
    {
        $code = (string) random_int(100000, 999999);

        DB::table('users')->where('id', $user->id)->update([
            'email_code'            => $code,
            'email_code_expires_at' => now()->addMinutes(15),
            'updated_at'            => now(),
        ]);

        // Branded HTML email with the code, plus a "check spam" notice.
        Mail::send('emails.verify-code', ['code' => $code, 'name' => $user->name], function ($m) use ($user, $code) {
            $m->to($user->email)
              ->subject(__(':app verification code: :code', [
                  'app'  => config('app.name', 'IBSolutions'),
                  'code' => $code,
              ]));
        });
    }

    /** Resend a fresh code (rate-limited). */
    public function resend(Request $request)
    {
        $user = $request->user();

        if ($user->email_verified_at) {
            return redirect()->route('dashboard');
        }

        $key = 'resend-code:' . $user->id;
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            return back()->withErrors(['code' => __('Please wait :s seconds before requesting another code.', ['s' => $seconds])]);
        }
        RateLimiter::hit($key, 120); // 3 per 2 minutes

        self::sendCode($user);

        return back()->with('status', __('A new code has been sent to your email.'));
    }

    /** Verify the submitted code. */
    public function verify(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $user = $request->user();

        if ($user->email_verified_at) {
            return redirect()->route('dashboard');
        }

        // Throttle guesses.
        $key = 'verify-code:' . $user->id;
        if (RateLimiter::tooManyAttempts($key, 6)) {
            throw ValidationException::withMessages(['code' => __('Too many attempts. Please request a new code.')]);
        }

        $row = DB::table('users')->where('id', $user->id)
            ->first(['email_code', 'email_code_expires_at']);

        $valid = $row
            && $row->email_code
            && hash_equals($row->email_code, $data['code'])
            && $row->email_code_expires_at
            && now()->lessThan($row->email_code_expires_at);

        if (! $valid) {
            RateLimiter::hit($key, 600);
            throw ValidationException::withMessages(['code' => __('That code is invalid or has expired.')]);
        }

        // Verified — clear the code.
        DB::table('users')->where('id', $user->id)->update([
            'email_verified_at'     => now(),
            'email_code'            => null,
            'email_code_expires_at' => null,
            'updated_at'            => now(),
        ]);
        RateLimiter::clear($key);

        return redirect()->route('dashboard')->with('status', __('Your email has been verified.'));
    }
}