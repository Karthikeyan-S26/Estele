<?php

namespace App\Services\Otp;

use App\Models\OtpCode;
use Illuminate\Support\Facades\Hash;

/**
 * Single entry point the auth controller calls for phone OTP login — mirrors
 * PaymentManager/ShippingManager's role in this codebase (one call site,
 * gateway swap is a constructor change, not a rewrite).
 */
class OtpManager
{
    private const CODE_LENGTH = 6;

    private const EXPIRY_MINUTES = 5;

    private const MAX_ATTEMPTS = 5;

    public function __construct(
        private readonly LogOtpGateway $logGateway,
        private readonly VasMultimediaOtpGateway $vasGateway,
        private readonly TwilioOtpGateway $twilioGateway,
        private readonly MailOtpGateway $mailGateway,
    ) {}

    /**
     * SMS_DRIVER picks explicitly between the two real gateways when both
     * happen to be configured at once (added after VAS Multimedia's
     * credentials kept returning "SUCCESS" from the API across three live
     * test sends without ever actually reaching a phone — Twilio was wired
     * up as a second real gateway to test against, not a replacement, since
     * neither one has yet been confirmed to actually deliver to an Indian
     * number end to end). No driver set (or an unrecognized one) falls back
     * to "whichever configured gateway comes first", same
     * "isConfigured() ? real : fallback" shape as
     * PaymentManager::isOnlinePaymentEnabled()/ShippingManager's flat-rate
     * fallback elsewhere in this codebase — logging is always the last resort.
     */
    private function gateway(): OtpGateway
    {
        return match (config('services.sms.driver')) {
            'vas_multimedia' => $this->vasGateway->isConfigured() ? $this->vasGateway : $this->logGateway,
            'twilio' => $this->twilioGateway->isConfigured() ? $this->twilioGateway : $this->logGateway,
            default => match (true) {
                $this->vasGateway->isConfigured() => $this->vasGateway,
                $this->twilioGateway->isConfigured() => $this->twilioGateway,
                default => $this->logGateway,
            },
        };
    }

    /**
     * Generates and sends a fresh code, invalidating any still-outstanding
     * code for the same number first (so only the most recently sent code
     * ever verifies).
     */
    public function issue(string $phone): void
    {
        $code = (string) random_int(10 ** (self::CODE_LENGTH - 1), (10 ** self::CODE_LENGTH) - 1);

        OtpCode::where('phone', $phone)
            ->where('channel', 'sms')
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);

        OtpCode::create([
            'phone' => $phone,
            'channel' => 'sms',
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(self::EXPIRY_MINUTES),
        ]);

        $this->gateway()->send($phone, $code);
    }

    /**
     * Email channel twin of issue() — same lifecycle, keyed by email address
     * instead of phone, delivered through the framework mail transport.
     */
    public function issueEmail(string $email): void
    {
        $email = $this->normalizeEmail($email);

        $code = (string) random_int(10 ** (self::CODE_LENGTH - 1), (10 ** self::CODE_LENGTH) - 1);

        OtpCode::where('email', $email)
            ->where('channel', 'email')
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);

        OtpCode::create([
            'email' => $email,
            'channel' => 'email',
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(self::EXPIRY_MINUTES),
        ]);

        $this->mailGateway->send($email, $code);
    }

    /**
     * Checks $code against the most recent unconsumed, unexpired code for
     * $phone. Consumes it (success or not, once found) so a code is single-use
     * either way — success marks it consumed immediately after verifying;
     * repeated failed attempts against the same code are capped instead of
     * consuming it outright, so a mistyped-but-correct-next-try code still works.
     *
     * Gateways that own verification remotely (see OtpVerifier — today Twilio
     * Verify, which generates and checks the code itself, so a locally
     * generated plaintext is never compared) are asked directly; every other
     * gateway keeps the local code_hash check exactly as before.
     */
    public function verify(string $phone, string $code): bool
    {
        $otp = OtpCode::where('phone', $phone)
            ->where('channel', 'sms')
            ->whereNull('consumed_at')
            ->where('expires_at', '>=', now())
            ->latest('id')
            ->first();

        if (! $otp || $otp->attempts >= self::MAX_ATTEMPTS) {
            return false;
        }

        $otp->increment('attempts');

        $gateway = $this->gateway();

        $correct = $gateway instanceof OtpVerifier
            ? $gateway->verify($phone, $code)
            : Hash::check($code, $otp->code_hash);

        if (! $correct) {
            return false;
        }

        $otp->update(['consumed_at' => now()]);

        return true;
    }

    /**
     * Email channel twin of verify() — single-use, attempt-capped, expired
     * codes never verify, wrong codes count toward the attempt cap.
     */
    public function verifyEmail(string $email, string $code): bool
    {
        $email = $this->normalizeEmail($email);

        $otp = OtpCode::where('email', $email)
            ->where('channel', 'email')
            ->whereNull('consumed_at')
            ->where('expires_at', '>=', now())
            ->latest('id')
            ->first();

        if (! $otp || $otp->attempts >= self::MAX_ATTEMPTS) {
            return false;
        }

        $otp->increment('attempts');

        if (! Hash::check($code, $otp->code_hash)) {
            return false;
        }

        $otp->update(['consumed_at' => now()]);

        return true;
    }

    private function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }
}
