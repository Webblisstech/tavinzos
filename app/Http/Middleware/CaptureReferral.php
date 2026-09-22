<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * When someone arrives with ?ref=CODE, remember it in a cookie so the referral
 * still attaches at registration even if they browse around first.
 */
class CaptureReferral
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $ref = $request->query('ref');
        if ($ref && ! $request->cookie('ref')) {
            // 30 days, so a referral link has a fair window to convert.
            $response->headers->setCookie(cookie('ref', substr($ref, 0, 12), 60 * 24 * 30));
        }

        return $response;
    }
}