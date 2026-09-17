<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Lets a customer's storefront session share one browser with an admin
 * panel session OR a vendor portal session, without either signing the
 * other out.
 *
 * There is only one auth guard in this app ('web' — see config/auth.php),
 * used by the OTP-only storefront login (App\Http\Controllers\
 * OtpAuthController), the Filament admin panel's email+password login, and
 * the vendor portal's email+password login (App\Http\Controllers\Vendor\
 * VendorAuthController). Logging in as one necessarily replaces whichever
 * of the others was in that single guard slot.
 *
 * The fix used here is a session "parking" trick rather than a second guard:
 * a second guard was tried and reverted (it required threading an explicit
 * guard through ~15 call sites — every hasRole()/assignRole()/Role query in
 * the app — since Spatie's guard auto-detection falls back to 'web' outside
 * an authenticated Filament request, which silently broke role checks in
 * queued jobs and customer-triggered notifications). Instead:
 *
 * 1. App\Filament\Pages\Auth\Login::mount() (admin) and
 *    App\Http\Controllers\Vendor\VendorAuthController::showLogin() (vendor)
 *    each park the current session's occupant (if there is one) via park()
 *    below, then log them out so their own login form loads instead of
 *    silently inheriting someone else's session.
 * 2. App\Http\Controllers\Auth\AdminLogoutController and
 *    VendorAuthController::logout() restore the parked user (if any) via
 *    restore() below, in the SAME request that logs the admin/vendor out —
 *    critically, before Filament's own logout would call
 *    session()->invalidate() and destroy the parked id, which is why the
 *    admin side needs its own logout controller instead of the package's.
 */
class PanelSessionParking
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
