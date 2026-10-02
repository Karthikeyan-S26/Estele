<?php

namespace Tests\Feature;

use App\Mail\OtpMail;
use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * POST /api/login/email/* + /api/register/email/* — the EMAIL OTP channel,
 * passenger twin of the phone-OTP flow. Codes are delivered through the
 * framework mail transport (MailOtpGateway -> OtpMail); tests use Mail::fake()
 * and read the plaintext from the captured mailable exactly as a subscriber
 * would. Mirrors the phone tests' security matrix: hashed storage, single-use,
 * expiry, enumeration-safe send, second-channel register support.
 */
class VerifyEmailOtpApiFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private function sentCode(string $email = 'demo@estele.in'): string
    {
        $mails = Mail::sent(OtpMail::class);

        $this->assertNotEmpty($mails, 'expected an OtpMail to have been sent');

        $mail = $mails->last();
        $this->assertSame($email, collect($mail->to)->first()['address'] ?? null);

        return $mail->code;
    }

    public function test_login_email_otp_send_delivers_a_code_to_the_address(): void
    {
        User::factory()->create(['email' => 'demo@estele.in']);

        $this->postJson('/api/login/email/send-otp', ['email' => 'demo@estele.in'])
            ->assertOk();

        $this->assertNotEmpty(Mail::sent(OtpMail::class));
    }

    public function test_login_email_otp_full_flow_logs_an_existing_user_in(): void
    {
        $user = User::factory()->create(['email' => 'demo@estele.in']);

        $this->postJson('/api/login/email/send-otp', ['email' => 'demo@estele.in'])->assertOk();
        $code = $this->sentCode();

        $response = $this->postJson('/api/login/email/verify-otp', [
            'email' => 'demo@estele.in',
            'code' => $code,
        ])->assertOk();

        $response->assertJsonPath('data.user.email', 'demo@estele.in')
            ->assertJsonStructure(['data' => ['user' => ['id', 'name', 'email'], 'token']]);
    }

    public function test_login_email_otp_rejects_the_wrong_code(): void
    {
        User::factory()->create(['email' => 'demo@estele.in']);

        $this->postJson('/api/login/email/send-otp', ['email' => 'demo@estele.in'])->assertOk();

        $this->postJson('/api/login/email/verify-otp', [
            'email' => 'demo@estele.in',
            'code' => '000000',
        ])->assertStatus(422)->assertJsonValidationErrors('code');
    }

    public function test_login_email_otp_is_enumeration_safe_for_unknown_addresses(): void
    {
        // Same generic payload whether the address has an account or not, and
        // (like the phone path) no OTP row / no mail for an unknown address.
        $this->postJson('/api/login/email/send-otp', ['email' => 'nobody@example.com'])
            ->assertOk()
            ->assertJsonPath('message', 'If an account exists for this email, an OTP has been sent to it.');

        $this->assertDatabaseMissing('otp_codes', ['email' => 'nobody@example.com', 'channel' => 'email']);
        $this->assertEmpty(Mail::sent(OtpMail::class));
    }

    public function test_login_email_otp_only_accepts_the_most_recently_sent_code(): void
    {
        User::factory()->create(['email' => 'demo@estele.in']);

        $this->postJson('/api/login/email/send-otp', ['email' => 'demo@estele.in'])->assertOk();
        $first = $this->sentCode();

        $this->postJson('/api/login/email/send-otp', ['email' => 'demo@estele.in'])->assertOk();

        $this->postJson('/api/login/email/verify-otp', ['email' => 'demo@estele.in', 'code' => $first])
            ->assertStatus(422);
    }

    public function test_login_email_otp_code_expires(): void
    {
        User::factory()->create(['email' => 'demo@estele.in']);

        $this->postJson('/api/login/email/send-otp', ['email' => 'demo@estele.in'])->assertOk();
        $code = $this->sentCode();

        OtpCode::where('email', 'demo@estele.in')->update(['expires_at' => now()->subMinute()]);

        $this->postJson('/api/login/email/verify-otp', ['email' => 'demo@estele.in', 'code' => $code])
            ->assertStatus(422);
    }

    public function test_email_code_is_stored_hashed_not_plaintext(): void
    {
        User::factory()->create(['email' => 'demo@estele.in']);

        $this->postJson('/api/login/email/send-otp', ['email' => 'demo@estele.in'])->assertOk();
        $code = $this->sentCode();

        $otp = OtpCode::where('email', 'demo@estele.in')->first();

        $this->assertNotSame($code, $otp->code_hash);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check($code, $otp->code_hash));
    }

    public function test_register_email_otp_full_flow_creates_and_logs_in_a_user(): void
    {
        $this->postJson('/api/register/email/send-otp', ['email' => 'newbie@estele.in'])
            ->assertOk()
            ->assertJsonPath('message', 'An OTP has been sent to your email.');
        $code = $this->sentCode('newbie@estele.in');

        $verified = $this->postJson('/api/register/email/verify-otp', [
            'email' => 'newbie@estele.in',
            'code' => $code,
        ])->assertOk();

        $token = $verified->json('data.verification_token');
        $this->assertNotEmpty($token);

        $response = $this->postJson('/api/register', [
            'name' => 'New Customer',
            'email' => 'newbie@estele.in',
            'phone' => '9899000011',
            'password' => 'secret-pass-123',
            'password_confirmation' => 'secret-pass-123',
            'verification_token' => $token,
        ])->assertStatus(201);

        $response->assertJsonPath('data.user.email', 'newbie@estele.in')
            ->assertJsonStructure(['data' => ['user', 'token']]);

        $this->assertDatabaseHas('users', ['email' => 'newbie@estele.in']);
    }

    public function test_register_verification_token_is_single_use(): void
    {
        $this->postJson('/api/register/email/send-otp', ['email' => 'oneuse@estele.in'])->assertOk();
        $verified = $this->postJson('/api/register/email/verify-otp', [
            'email' => 'oneuse@estele.in',
            'code' => $this->sentCode('oneuse@estele.in'),
        ])->assertOk();
        $token = $verified->json('data.verification_token');

        $payload = [
            'name' => 'Once User',
            'email' => 'oneuse@estele.in',
            'phone' => '9899000022',
            'password' => 'secret-pass-123',
            'password_confirmation' => 'secret-pass-123',
            'verification_token' => $token,
        ];

        $this->postJson('/api/register', $payload)->assertStatus(201);

        // Second registration with the same (now consumed) nonce must fail.
        $this->postJson('/api/register', $payload)->assertStatus(422);
    }

    public function test_register_email_otp_is_enumeration_safe_for_registered_addresses(): void
    {
        User::factory()->create(['email' => 'taken@estele.in']);

        $this->postJson('/api/register/email/send-otp', ['email' => 'taken@estele.in'])
            ->assertOk()
            ->assertJsonPath('message', 'An OTP has been sent to your email.');

        $this->assertDatabaseMissing('otp_codes', ['email' => 'taken@estele.in', 'channel' => 'email']);
        $this->assertEmpty(Mail::sent(OtpMail::class));
    }

    public function test_phone_otp_register_still_works_with_the_email_channel_present(): void
    {
        // Regression guard: the phone-OTP registration path must be unchanged.
        $this->postJson('/api/register/send-otp', ['phone' => '9876540000'])->assertOk();

        $otp = OtpCode::where('phone', '9876540000')
            ->where('channel', 'sms')
            ->firstOrFail();
        $code = '123456';
        $otp->update(['code_hash' => \Illuminate\Support\Facades\Hash::make($code)]);

        $verified = $this->postJson('/api/register/verify-otp', ['phone' => '9876540000', 'code' => $code])
            ->assertOk();

        $this->postJson('/api/register', [
            'name' => 'SMS User',
            'email' => 'sms-reg@estele.in',
            'phone' => '9876540000',
            'password' => 'secret-pass-123',
            'password_confirmation' => 'secret-pass-123',
            'verification_token' => $verified->json('data.verification_token'),
        ])->assertStatus(201);

        $this->assertDatabaseHas('users', ['phone' => '9876540000']);
    }
}