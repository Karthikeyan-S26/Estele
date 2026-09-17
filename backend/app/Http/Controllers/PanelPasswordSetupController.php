<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

/**
 * Where a panel invite lands: the recipient chooses their own password, then
 * signs in — at the vendor portal login for a vendor contact, at the normal
 * admin login for everyone else (staff, or an 'admin'-access contact — see
 * User::canAccessPanel()). Uses the 'panel_invites' broker (48h) rather than
 * the 60-minute self-service one.
 */
class PanelPasswordSetupController extends Controller
{
    public function show(Request $request, string $token): View
    {
        return view('auth.panel-password-setup', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $isVendor = false;

        $status = Password::broker('panel_invites')->reset(
            $validated,
            function (User $user, string $password) use (&$isVendor) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                $isVendor = $user->vendor()->where('access_role', 'vendor')->exists();

                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PasswordReset) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => __($status)]);
        }

        return redirect($isVendor ? route('vendor.login') : '/admin/login')
            ->with('status', 'Your password has been set. Please sign in.');
    }
}
