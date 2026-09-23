<?php

use App\Http\Middleware\CaptureReferral;
use App\Http\Middleware\CheckMaintenance;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureEmailVerified;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Runs on every web request, in order:
        //  1. capture ?ref= into a cookie for referrals,
        //  2. gate the site when maintenance mode is on (admins/auth pass),
        //  3. send unverified customers to the code page until they verify.
        $middleware->web(append: [
            CaptureReferral::class,
            CheckMaintenance::class,
            EnsureEmailVerified::class,
        ]);

        $middleware->alias([
            'admin' => EnsureAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // A POST/PUT/DELETE route reached with GET (someone typing an action URL
        // into the address bar) throws MethodNotAllowed. Rather than show a raw
        // 405 page, send web visitors somewhere sensible; APIs still get JSON.
        $exceptions->render(function (
            \Symfony\Component\Routing\Exception\MethodNotAllowedException|\Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException $e,
            Request $request
        ) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This action must be submitted from the app, not opened directly.',
                ], 405);
            }

            // Only GET lands a human on such a URL — bounce them back.
            if ($request->isMethod('GET')) {
                $fallback = \Illuminate\Support\Facades\Route::has('dashboard') ? route('dashboard') : url('/');
                $target = url()->previous() ?: $fallback;
                return redirect($target)->with('error', __('That action can\'t be opened directly.'));
            }

            return null; // let other methods fall through to the default handler
        });
    })->create();