<?php

namespace App\Http\Controllers\Auth;

use App\Support\AdminSessionParking;
use Filament\Auth\Http\Responses\Contracts\LogoutResponse;
use Filament\Facades\Filament;

/**
 * Replaces Filament's own LogoutController (see AdminPanelProvider, which
 * registers this at the same '/admin/logout' path before the package's own
 * route so this one wins) for exactly one reason: the package version calls
 * session()->invalidate(), which would destroy the parked customer id
 * AdminSessionParking::park() stashed at admin-login time before it could
 * ever be restored. Restoring first, in the same request, then logging out
 * without nuking the whole session gets the same practical result (no stale
 * admin session left behind) without losing that parked id.
 */
class AdminLogoutController
{
    public function __invoke(): LogoutResponse
    {
        Filament::auth()->logout();

        session()->regenerateToken();

        AdminSessionParking::restore();

        return app(LogoutResponse::class);
    }
}
