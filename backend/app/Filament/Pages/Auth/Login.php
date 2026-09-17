<?php

namespace App\Filament\Pages\Auth;

use App\Support\AdminSessionParking;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\Hidden;
use Filament\Schemas\Components\Component;
use Illuminate\Support\Facades\Auth;

class Login extends BaseLogin
{
    /**
     * The panel shares the customer-facing 'web' guard (see
     * AdminSessionParking's docblock for why there is no separate 'admin'
     * guard), so a customer logged in on the storefront in this same
     * browser is sitting in the exact session slot an admin login is about
     * to overwrite. Park that customer's id first — AdminLogoutController
     * restores it when the admin signs out — so opening /admin/login never
     * silently signs the customer out of the storefront tab.
     */
    public function mount(): void
    {
        // An OTP-only customer (no password — see User::canAccessPanel())
        // about to be replaced by an admin login: park them so they come
        // back exactly as they were when the admin signs out later.
        if (Auth::check() && blank(Auth::user()->password)) {
            AdminSessionParking::park(Auth::user());
        }

        parent::mount();
    }

    protected function getRememberFormComponent(): Component
    {
        return Hidden::make('remember')->default(true);
    }
}
