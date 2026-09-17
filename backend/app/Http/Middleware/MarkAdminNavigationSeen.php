<?php

namespace App\Http\Middleware;

use App\Filament\Support\NavigationSeen;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MarkAdminNavigationSeen
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET') && preg_match('/^filament\.admin\.resources\.(.+)\.index$/', (string) $request->route()?->getName(), $matches)) {
            NavigationSeen::mark($matches[1]);
        }

        return $next($request);
    }
}
