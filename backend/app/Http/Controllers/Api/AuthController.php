<?php

namespace App\Http\Controllers\Api;

use App\Http\Api\ApiResponses;
use App\Models\Cart;
use App\Models\OtpCode;
use App\Models\User;
use App\Services\Otp\OtpManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController
{
    use ApiResponses;

    public function __construct(private readonly OtpManager $otp) {}

    /**
     * POST /api/register — email + password based account creation. The phone
     * must have already completed the OTP verify step (verifyRegisterOtp below),
     * which stamps a single-use `verification_token` on the consumed OtpCode
     * row — the client passes it back here and we validate + consume it
     * server-side, so an account can never be created against an unverified
     * phone (previously a client-set X-Phone-Verified header was trusted).
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'digits:10', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'verification_token' => ['required', 'string', 'max:255'],
        ]);

        $otp = OtpCode::where('phone', $validated['phone'])
            ->where('verified_token', $validated['verification_token'])
            ->where('verified_at', '>=', now()->subMinutes(30))
            ->first();

        if (! $otp) {
            return $this->error('Please verify your mobile number with OTP before creating your account.', 422, [
                'phone' => ['Please verify your mobile number with OTP before creating your account.'],
            ]);
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
        ]);

        // Single-use: consume the verification nonce so it can't register twice.
        $otp->update(['verified_token' => null, 'verified_at' => null]);

        // Merge any guest cart bound to this phone's pre-registration cart token.
        if ($cartToken = $request->header('X-Cart-Token')) {
            if ($guestCart = Cart::where('session_id', $cartToken)->first()) {
                $userCart = Cart::firstOrCreate(['user_id' => $user->id]);
                Cart::mergeCarts($guestCart, $userCart);
            }
        }

        $token = $user->createApiToken();

        return $this->created([
            'user' => $this->userPayload($user),
            'token' => $token,
        ]);
    }

    /**
     * POST /api/register/send-otp — issue a 6-digit OTP for a phone number that
     * is NOT already registered.
     */
    public function sendRegisterOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'digits:10'],
        ]);

        if (User::where('phone', $validated['phone'])->exists()) {
            return $this->error('This phone number is already registered. Please log in.', 422, [
                'phone' => ['This phone number is already registered. Please log in.'],
            ]);
        }

        // OTP is keyed by phone+code in the database — the "verified" intent is
        // tracked client-side on the phone hash so a phone can't be "verified"
        // for a different phone's pending OTP.
        $this->otp->issue($validated['phone']);

        return $this->message('An OTP has been sent to your phone.');
    }

    /**
     * POST /api/register/verify-otp — check the OTP code and return a
     * server-issued, single-use `verification_token` the client passes back to
     * /api/register. The token is stamped on the consumed OtpCode row
     * (server-side truth), not a client-set HTTP header.
     */
    public function verifyRegisterOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'digits:10'],
            'code' => ['required', 'digits:6'],
        ]);

        if (! $this->otp->verify($validated['phone'], $validated['code'])) {
            return $this->error('That code is incorrect or has expired.', 422, [
                'code' => ['That code is incorrect or has expired.'],
            ]);
        }

        $otp = OtpCode::where('phone', $validated['phone'])
            ->whereNotNull('consumed_at')
            ->whereNull('verified_token')
            ->latest('id')
            ->first();

        if (! $otp) {
            return $this->error('Please request a fresh OTP.', 422, [
                'phone' => ['Please request a fresh OTP.'],
            ]);
        }

        $token = Str::random(64);
        $otp->update([
            'verified_token' => $token,
            'verified_at' => now(),
        ]);

        return $this->ok([
            'verification_token' => $token,
            'message' => 'Phone verified. You can now complete registration.',
        ]);
    }

    /**
     * POST /api/login — email + password. Returns a bearer token.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return $this->error('These credentials do not match our records.', 401, [
                'email' => ['These credentials do not match our records.'],
            ]);
        }

        // Merge any guest cart into the user's cart before first token issue.
        if ($cartToken = $request->header('X-Cart-Token')) {
            if ($guestCart = Cart::where('session_id', $cartToken)->first()) {
                $userCart = Cart::firstOrCreate(['user_id' => $user->id]);
                Cart::mergeCarts($guestCart, $userCart);
                Cart::where('session_id', $cartToken)->delete();
            }
        }

        $token = $user->createApiToken();

        return $this->ok([
            'user' => $this->userPayload($user),
            'token' => $token,
        ]);
    }

    /**
     * POST /api/login/mobile/send-otp — OTP login for an EXISTING account.
     */
    public function sendLoginOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'digits_between:10,15'],
        ]);

        if (! User::where('phone', $validated['phone'])->exists()) {
            return $this->error('No account found for this phone number.', 422, [
                'phone' => ['No account found for this phone number. Please register instead.'],
            ]);
        }

        $this->otp->issue($validated['phone']);

        return $this->message('An OTP has been sent to your phone.');
    }

    /**
     * POST /api/login/mobile/verify-otp — verify the OTP and log the user in.
     */
    public function verifyLoginOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'digits_between:10,15'],
            'code' => ['required', 'digits:6'],
        ]);

        if (! $this->otp->verify($validated['phone'], $validated['code'])) {
            return $this->error('That code is incorrect or has expired.', 422, [
                'code' => ['That code is incorrect or has expired.'],
            ]);
        }

        $user = User::where('phone', $validated['phone'])->first();
        if (! $user) {
            return $this->error('No account found for this phone number.', 422, [
                'phone' => ['No account found for this phone number.'],
            ]);
        }

        if ($cartToken = $request->header('X-Cart-Token')) {
            if ($guestCart = Cart::where('session_id', $cartToken)->first()) {
                $userCart = Cart::firstOrCreate(['user_id' => $user->id]);
                Cart::mergeCarts($guestCart, $userCart);
                Cart::where('session_id', $cartToken)->delete();
            }
        }

        $token = $user->createApiToken();

        return $this->ok([
            'user' => $this->userPayload($user),
            'token' => $token,
        ]);
    }

    /**
     * POST /api/logout — revoke every bearer token this user holds (auth required).
     */
    public function logout(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $user->revokeAllApiTokens();

        return $this->message('Logged out successfully.');
    }

    /**
     * POST /api/forgot-password — email a password reset link (or, since the
     * app has no real mail in dev, return a generic confirmation).
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::broker()->sendResetLink(['email' => $validated['email']]);

        return match ($status) {
            Password::RESET_LINK_SENT => $this->message('We have emailed your password reset link.'),
            default => $this->message('We have emailed your password reset link.'),
        };
    }

    /**
     * POST /api/reset-password — complete a password reset with the emailed token.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::broker()->reset(
            $validated,
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        return match ($status) {
            Password::PASSWORD_RESET => $this->message('Your password has been reset. You can now log in.'),
            default => $this->error('Unable to reset the password with the token provided.', 400),
        };
    }

    /**
     * The mobile app authenticates the bare-bearer token we issue (no id|suffix
     * like Sanctum) — the guard already handles both, this just returns the
     * current user for GET /api/account (self).
     */
    public function me(Request $request): JsonResponse
    {
        return $this->ok($this->userPayload($request->user()));
    }

    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'wallet_balance' => (float) $user->wallet_balance,
            'created_at' => $user->created_at?->toIso8601String(),
        ];
    }
}