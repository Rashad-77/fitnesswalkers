<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Standard browser-protection headers on every page. (No strict Content-Security-Policy yet: the pages use inline scripts.) */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $r = $next($request);
        $r->headers->set('X-Content-Type-Options', 'nosniff');
        $r->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $r->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $r->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        if ($request->isSecure()) $r->headers->set('Strict-Transport-Security', 'max-age=31536000');
        return $r;
    }
}
