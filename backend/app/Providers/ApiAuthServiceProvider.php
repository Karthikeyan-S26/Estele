<?php

namespace App\Providers;

use App\Auth\TokenGuard;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class ApiAuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Auth::extend('api-token', function (Application $app, string $name, array $config) {
            $provider = Auth::createUserProvider($config['provider'] ?? 'users');

            return new TokenGuard($provider, $app['request']);
        });

        // Mobile API 401 responses should be JSON, not a redirect to the
        // web login page.
        Authenticate::redirectUsing(function ($request) {
            if ($request->is('api/*')) {
                abort(401, __('Unauthenticated.'));
            }

            session()->flash('error', 'Please log in to continue.');

            return route('login');
        });
    }
}