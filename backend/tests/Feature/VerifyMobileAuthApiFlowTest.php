<?php

namespace Tests\Feature;

use App\Models\OtpCode;
use App\Models\User;
use App\Services\Otp\LogOtpGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * POST /api/auth/mobile/* — the website-parity unified mobile auth round the app
 * now uses (and the only OTP path the mobile app calls). One OTP serves both
 * login and registration, mirroring the website's OtpAuthController:
 *   existing number -> bearer token (data.account = "existing")
 *   new number      -> single-use registration nonce (data.account = "new")
 *
 * Registration is name + verified phone only (email/password optional) so the
 * app's account completion matches the website's register form. Reuses the
 * existing OtpManager — every shared OTP rule still applies.
 */
class VerifyMobileAuthApiFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        RecordingOtpGatewayForMobileAuthTests::$lastCode = null;
        $this->app->bind(LogOtpGateway::class, RecordingOtpGatewayForMobileAuthTests::class);
    }

    private function lastCode(): string
    {
        $code = RecordingOtpGatewayForMobileAuthTests::$lastCode;
        $this->assertNotNull($code, 'expected an OTP to have been sent to the recording gateway');

        return $code;
    }

    public function test_send_issues_an_otp_for_any_phone_registered_or_not(): void
    {
        User::factory()->create(['phone' => '9876543210']);

        // New (unregistered) phone.
        $this->postJson('/api/auth/mobile/send-otp', ['phone' => '9998887770'])
            ->assertOk()
            ->assertJsonPath('message', 'An OTP has been sent to your phone.');

        $otp = OtpCode::where('phone', '9998887770')->where('channel', 'sms')->first();
        $this->assertNotNull($otp);
        $this->assertNotSame($this->lastCode(), $otp->code_hash);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check($this->lastCode(), $otp->code_hash));

        // Registered phone gets the same round (and its own fresh OTP).
        $this->postJson('/api/auth/mobile/send-otp', ['phone' => '9876543210'])->assertOk();

        $this->assertNotNull(
            OtpCode::where('phone', '9876543210')->where('channel', 'sms')->first()
        );
    }

    public function test_verify_logs_an_existing_number_in_with_a_bearer_token(): void
    {
        $user = User::factory()->create(['phone' => '9876543210']);

        $this->postJson('/api/auth/mobile/send-otp', ['phone' => '9876543210'])->assertOk();

        $response = $this->postJson('/api/auth/mobile/verify-otp', [
            'phone' => '9876543210',
            'code' => $this->lastCode(),
        ])->assertOk();

        $response->assertJsonPath('data.account', 'existing')
            ->assertJsonPath('data.user.phone', '9876543210')
            ->assertJsonStructure(['data' => ['user' => ['id', 'name', 'email', 'phone'], 'token']]);

        $token = $response->json('data.token');
        $this->assertNotEmpty($token);
        $this->assertNotNull($user->fresh()->password, 'password-ful users continue to work');
    }

    public function test_verify_returns_a_registration_nonce_for_a_new_number(): void
    {
        $this->postJson('/api/auth/mobile/send-otp', ['phone' => '9998887770'])->assertOk();

        $response = $this->postJson('/api/auth/mobile/verify-otp', [
            'phone' => '9998887770',
            'code' => $this->lastCode(),
        ])->assertOk();

        $response->assertJsonPath('data.account', 'new');

        $nonce = $response->json('data.verification_token');
        $this->assertNotEmpty($nonce);

        $otp = OtpCode::where('phone', '9998887770')->first();
        $this->assertSame($nonce, $otp->verified_token);
        $this->assertNotNull($otp->verified_at);
    }

    public function test_register_completes_with_name_and_phone_only(): void
    {
        $this->postJson('/api/auth/mobile/send-otp', ['phone' => '9998887770'])->assertOk();

        $verified = $this->postJson('/api/auth/mobile/verify-otp', [
            'phone' => '9998887770',
            'code' => $this->lastCode(),
        ])->assertOk();

        $response = $this->postJson('/api/register', [
            'name' => 'New Customer',
            'phone' => '9998887770',
            'verification_token' => $verified->json('data.verification_token'),
        ])->assertStatus(201);

        $response->assertJsonPath('data.user.phone', '9998887770')
            ->assertJsonStructure(['data' => ['user' => ['id', 'name', 'email', 'phone'], 'token']]);

        $user = User::where('phone', '9998887770')->firstOrFail();
        $this->assertNull($user->email, 'email stays null when not provided');
        $this->assertNull($user->password, 'password stays null when not provided');

        // Nonce is single-use — a second register with it must fail.
        $this->postJson('/api/register', [
            'name' => 'Duplicate',
            'phone' => '9998887770',
            'verification_token' => $verified->json('data.verification_token'),
        ])->assertStatus(422);
    }

    public function test_register_rejects_an_unverified_phone(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Skipper',
            'phone' => '9990003333',
        ])->assertStatus(422)->assertJsonValidationErrors('verification_token');
    }

    public function test_bearer_token_from_mobile_verify_authenticates_api_calls(): void
    {
        $user = User::factory()->create(['phone' => '9876543210']);

        $this->postJson('/api/auth/mobile/send-otp', ['phone' => '9876543210'])->assertOk();

        $response = $this->postJson('/api/auth/mobile/verify-otp', [
            'phone' => '9876543210',
            'code' => $this->lastCode(),
        ])->assertOk();

        $token = $response->json('data.token');

        $this->withToken($token)
            ->getJson('/api/account')
            ->assertOk()
            ->assertJsonPath('data.phone', '9876543210');
    }

    public function test_wrong_code_is_rejected(): void
    {
        $this->postJson('/api/auth/mobile/send-otp', ['phone' => '9876543210'])->assertOk();

        $this->postJson('/api/auth/mobile/verify-otp', [
            'phone' => '9876543210',
            'code' => '000000',
        ])->assertStatus(422)->assertJsonValidationErrors('code');
    }

    public function test_the_same_code_cannot_be_used_twice(): void
    {
        User::factory()->create(['phone' => '9876543210']);

        $this->postJson('/api/auth/mobile/send-otp', ['phone' => '9876543210'])->assertOk();
        $code = $this->lastCode();

        $this->postJson('/api/auth/mobile/verify-otp', ['phone' => '9876543210', 'code' => $code])->assertOk();

        $this->postJson('/api/auth/mobile/verify-otp', ['phone' => '9876543210', 'code' => $code])
            ->assertStatus(422);
    }

    public function test_resend_invalidates_the_previous_code(): void
    {
        $this->postJson('/api/auth/mobile/send-otp', ['phone' => '9998887770'])->assertOk();
        $firstCode = $this->lastCode();

        $this->postJson('/api/auth/mobile/send-otp', ['phone' => '9998887770'])->assertOk();
        $secondCode = $this->lastCode();
        $this->assertNotSame($firstCode, $secondCode);

        $this->postJson('/api/auth/mobile/verify-otp', ['phone' => '9998887770', 'code' => $firstCode])
            ->assertStatus(422);

        $this->postJson('/api/auth/mobile/verify-otp', ['phone' => '9998887770', 'code' => $secondCode])
            ->assertOk();
    }

    public function test_an_expired_code_is_rejected(): void
    {
        $this->postJson('/api/auth/mobile/send-otp', ['phone' => '9998887770'])->assertOk();
        $code = $this->lastCode();

        OtpCode::where('phone', '9998887770')->update(['expires_at' => now()->subMinute()]);

        $this->postJson('/api/auth/mobile/verify-otp', ['phone' => '9998887770', 'code' => $code])
            ->assertStatus(422)->assertJsonValidationErrors('code');
    }

    public function test_phone_validation_allows_10_digit_numbers_only_via_the_app_shape(): void
    {
        $this->postJson('/api/auth/mobile/send-otp', ['phone' => '123'])
            ->assertStatus(422)->assertJsonValidationErrors('phone');
    }
}

/**
 * Test double: same shape as LogOtpGateway but captures the plaintext code
 * in a static instead of writing it to the log.
 */
class RecordingOtpGatewayForMobileAuthTests extends LogOtpGateway
{
    public static ?string $lastCode = null;

    public function send(string $phone, string $code): void
    {
        self::$lastCode = $code;
    }
}