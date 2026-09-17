<?php

namespace App\Http\Middleware;

use App\Models\Vendor;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards every /vendor/* route except the login screen itself. A customer,
 * staff member, or signed-out visitor is sent to the vendor login — never a
 * blank 403 — since "wrong portal, try the vendor login" is a normal thing
 * to land on (e.g. a stale bookmark), not a security incident worth hiding
 * behind a generic error page.
 */
class EnsureVendorAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Vendor::current() !== null) {
            return $next($request);
        }

        if (Auth::check()) {
            // Signed in as something else entirely (staff/customer) — say
            // so, rather than a silent redirect that looks like a dead link.
            return redirect()->route('vendor.login')
                ->with('error', 'That login is not a vendor account. Sign in with your vendor email instead.');
        }

        return redirect()->route('vendor.login');
    }
}
