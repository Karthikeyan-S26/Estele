<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\User;
use App\Services\Otp\OtpManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        private readonly OtpManager $otp
    ) {}

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        $oldSessionId = $request->session()->getId();

        $request->session()->regenerate();

        Cart::transferSession(
            $oldSessionId,
            $request->session()->getId()
        );

        return redirect()
            ->intended(route('account.index'))
            ->with('success', 'Welcome back!');
    }

    public function showRegister()
    {
        return view('auth.register', [
            'prefillPhone' => session('prefill_phone'),
        ]);
    }

    /**
     * Send OTP for new account registration.
     */
    public function sendRegistrationOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'digits:10'],
        ]);

        // Don't allow OTP registration for an existing phone number.
        if (User::where('phone', $validated['phone'])->exists()) {
            return response()->json([
                'message' => 'An account already exists with this mobile number.',
            ], 422);
        }

        // Generate and send OTP.
        $this->otp->issue($validated['phone']);

        // Remember which phone number requested the OTP.
        $request->session()->put(
            'registration_otp_phone',
            $validated['phone']
        );

        // A new OTP invalidates any previous registration verification.
        $request->session()->forget('registration_phone_verified');

        return response()->json([
            'success' => true,
            'message' => 'OTP sent successfully.',
        ]);
    }

    /**
     * Verify OTP for new account registration.
     */
    public function verifyRegistrationOtp(Request $request): JsonResponse
    {
        $phone = $request->session()->get('registration_otp_phone');

        if (! $phone) {
            return response()->json([
                'message' => 'Your OTP session has expired. Please request a new OTP.',
            ], 422);
        }

        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        if (! $this->otp->verify($phone, $validated['code'])) {
            return response()->json([
                'message' => 'That code is incorrect or has expired.',
            ], 422);
        }

        // Mark this phone number as successfully OTP verified.
        $request->session()->put(
            'registration_phone_verified',
            $phone
        );

        return response()->json([
            'success' => true,
            'message' => 'Mobile number verified successfully.',
        ]);
    }

    /**
     * Create the account only after mobile OTP verification.
     */
    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'digits:10', 'unique:users,phone'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        // Get the phone number that was actually verified through OTP.
        $verifiedPhone = $request->session()->get(
            'registration_phone_verified'
        );

        // Make sure the phone submitted in the form is the same
        // phone number that completed OTP verification.
        if (! $verifiedPhone || $verifiedPhone !== $validated['phone']) {
            throw ValidationException::withMessages([
                'phone' => 'Please verify your mobile number with OTP before creating your account.',
            ]);
        }

        // Create the user without a password.
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => filled($validated['password'] ?? null) ? Hash::make($validated['password']) : null,
        ]);

        // Remove temporary OTP registration session data.
        $request->session()->forget([
            'registration_otp_phone',
            'registration_phone_verified',
            'prefill_phone',
        ]);

        // Log the new user in automatically.
        $oldSessionId = $request->session()->getId();

        Auth::login($user);

        $request->session()->regenerate();

        Cart::transferSession(
            $oldSessionId,
            $request->session()->getId()
        );

        return redirect()
            ->intended(route('account.index'))
            ->with('success', 'Account created — welcome!');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('home')
            ->with('success', 'You have been logged out.');
    }
}