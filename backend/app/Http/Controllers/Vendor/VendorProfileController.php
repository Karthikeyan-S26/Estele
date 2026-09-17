<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

/**
 * Name, mobile, and company are set by the admin (mobile specifically stays
 * tied to the admin-run OTP verification — see VendorsTable::verifyOtpAction)
 * — a vendor can only change the WhatsApp number bid invites go to, and
 * their own password.
 */
class VendorProfileController extends Controller
{
    public function edit(): View
    {
        return view('vendor.portal.profile', ['vendor' => Vendor::current()]);
    }

    public function updateContact(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'whatsapp_number' => ['nullable', 'string', 'max:20'],
        ]);

        Vendor::current()->update($validated);

        return back()->with('success', 'Contact details updated.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        Auth::user()->forceFill([
            'password' => Hash::make($validated['password']),
        ])->save();

        return back()->with('success', 'Password changed.');
    }
}
