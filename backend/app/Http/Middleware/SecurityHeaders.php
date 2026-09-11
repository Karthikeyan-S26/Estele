<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Phase 6 security audit — Laravel sets none of these response headers by
 * default. All four are cheap, standard, and safe to apply site-wide (no
 * app behavior depends on being framed, sniffed, or leaking referrers).
 * HSTS is safe here specifically because the site is HTTPS-only in
 * production already (bootstrap/app.php trusts X-Forwarded-Proto from
 * Railway's edge) — it would be wrong to add if HTTP were still served.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
