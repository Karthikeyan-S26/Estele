<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Lets a customer's storefront session and an admin's Filament session share
 * one browser without either signing the other out.
 *
 * There is only one auth guard in this app ('web' — see config/auth.php),
 * used both by the OTP-only storefront login (App\Http\Controllers\
 * OtpAuthController) and by the Filament admin panel's email+password login.
 * Logging in as one necessarily replaces the other in that single guard slot.
 *
 * The fix used here is a session "parking" trick rather than a second guard:
 * a second guard was tried and reverted (it required threading an explicit
 * guard through ~15 call sites — every hasRole()/assignRole()/Role query in
 * the app — since Spatie's guard auto-detection falls back to 'web' outside
 * an authenticated Filament request, which silently broke role checks in
 * queued jobs and customer-triggered notifications). Instead:
 *
 * 1. App\Filament\Pages\Auth\Login::mount() parks the current session's
 *    customer (if there is one) via park() below, then logs them out so the
 *    admin login form loads instead of Filament bouncing straight past it.
 * 2. App\Http\Controllers\Auth\AdminLogoutController restores the parked
 *    customer (if any) via restore() below, in the SAME request that logs
 *    the admin out — critically, before Filament's own logout would call
 *    session()->invalidate() and destroy the parked id, which is why this
 *    needs its own controller instead of the package's LogoutController.
 */
class AdminSessionParking
{
    private const SESSION_KEY = 'parked_customer_id';

    public static function park(User $customer): void
    {
        session([self::SESSION_KEY => $customer->getKey()]);

        // Same "sign out without nuking session data" logout Laravel's own
        // SessionGuard::logout() does — no session()->invalidate() here,
        // that would erase the id just stored above.
        Auth::logout();
    }

    /**
     * Restores the parked customer, if there is one, and forgets the park.
     * No-ops quietly if the parked user no longer exists (e.g. deleted while
     * the admin was signed in) — there is nothing to sign back into.
     */
    public static function restore(): void
    {
        $customerId = session(self::SESSION_KEY);

        session()->forget(self::SESSION_KEY);

        if (! $customerId) {
            return;
        }

        $customer = User::find($customerId);

        if ($customer) {
            Auth::login($customer, remember: true);
        }
    }
}
