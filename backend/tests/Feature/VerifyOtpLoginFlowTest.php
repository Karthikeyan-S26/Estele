<?php

namespace Tests\Feature;

use App\Models\OtpCode;
use App\Models\User;
use App\Services\Otp\LogOtpGateway;
use App\Services\Otp\OtpManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Self-built phone + OTP login (no third-party gateway — see LogOtpGateway).
 * Covers the full send/verify loop, the registered-vs-not-registered branch,
 * and the security properties (hashed storage, single-use, expiry).
 *
 * Swaps in a recording gateway (below) so tests can read the plaintext code
 * the same way a real SMS gateway would receive it — OtpManager only ever
 * stores/checks the hash (see code_hash), it never exposes the plaintext
 * back out, so there's no legitimate way to read it except at send() time.
 */
class VerifyOtpLoginFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        RecordingOtpGatewayForTests::$lastCode = null;
        $this->app->bind(LogOtpGateway::class, RecordingOtpGatewayForTests::class);
    }

    public function test_sending_a_code_stores_it_hashed_not_plaintext(): void
    {
        app(OtpManager::class)->issue('9876543210');

        $otp = OtpCode::where('phone', '9876543210')->first();

        $this->assertNotNull($otp);
        $this->assertNotSame(RecordingOtpGatewayForTests::$lastCode, $otp->code_hash);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check(RecordingOtpGatewayForTests::$lastCode, $otp->code_hash));
    }

    public function test_verify_succeeds_with_the_correct_code(): void
    {
        $manager = app(OtpManager::class);
        $manager->issue('9876543210');

        $this->assertTrue($manager->verify('9876543210', RecordingOtpGatewayForTests::$lastCode));
    }

    public function test_verify_fails_with_the_wrong_code(): void
    {
        $manager = app(OtpManager::class);
        $manager->issue('9876543210');

        $this->assertFalse($manager->verify('9876543210', '000000'));
    }

    public function test_a_code_cannot_be_reused_after_a_successful_verify(): void
    {
        $manager = app(OtpManager::class);
        $manager->issue('9876543210');
        $code = RecordingOtpGatewayForTests::$lastCode;

        $this->assertTrue($manager->verify('9876543210', $code));
        $this->assertFalse($manager->verify('9876543210', $code));
    }

    public function test_an_expired_code_does_not_verify(): void
    {
        $manager = app(OtpManager::class);
        $manager->issue('9876543210');
        $code = RecordingOtpGatewayForTests::$lastCode;
        OtpCode::where('phone', '9876543210')->update(['expires_at' => now()->subMinute()]);

        $this->assertFalse($manager->verify('9876543210', $code));
    }

    public function test_issuing_a_new_code_invalidates_the_previous_outstanding_one(): void
    {
        $manager = app(OtpManager::class);
        $manager->issue('9876543210');
        $firstCode = RecordingOtpGatewayForTests::$lastCode;

        $manager->issue('9876543210');

        $this->assertFalse($manager->verify('9876543210', $firstCode));
    }

    public function test_full_http_flow_logs_in_an_existing_user_by_phone(): void
    {
        $user = User::factory()->create(['phone' => '9876543210']);

        $this->post('/login', ['phone' => '9876543210'])
            ->assertRedirect(route('login.verify'));

        $this->post('/login/verify', ['code' => RecordingOtpGatewayForTests::$lastCode])
            ->assertRedirect(route('account.index'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_full_http_flow_redirects_an_unregistered_phone_to_registration_with_prefill(): void
    {
        $this->post('/login', ['phone' => '9998887770']);

        $response = $this->post('/login/verify', ['code' => RecordingOtpGatewayForTests::$lastCode]);

        $response->assertRedirect(route('register'));
        $this->assertGuest();

        // The flashed phone reaches the registration form's prefill.
        $this->get(route('register'))->assertSee('9998887770', false);
    }

    private function registerViaOtp(string $phone, array $extra = []): \Illuminate\Testing\TestResponse
    {
        $this->post('/login', ['phone' => $phone])->assertRedirect(route('login.verify'));
        $this->post('/login/verify', ['code' => RecordingOtpGatewayForTests::$lastCode])
            ->assertRedirect(route('register'));

        return $this->post('/register', array_merge([
            'name' => 'Reg User',
            'phone' => $phone,
        ], $extra));
    }

    public function test_registration_without_password_creates_an_otp_only_account(): void
    {
        $this->registerViaOtp('9990001111')->assertRedirect(route('account.index'));

        $this->assertAuthenticated();
        $this->assertNull(User::where('phone', '9990001111')->firstOrFail()->password);
    }

    public function test_an_otp_only_account_can_log_back_in_with_a_fresh_otp(): void
    {
        $this->registerViaOtp('9990002222')->assertRedirect(route('account.index'));

        $this->post('/logout');
        $this->assertGuest();

        $otpOnly = User::where('phone', '9990002222')->firstOrFail();
        $this->assertNull($otpOnly->password);

        $this->post('/login', ['phone' => '9990002222'])->assertRedirect(route('login.verify'));
        $this->post('/login/verify', ['code' => RecordingOtpGatewayForTests::$lastCode])
            ->assertRedirect(route('account.index'));
        $this->assertAuthenticatedAs($otpOnly);
    }

    public function test_registration_requires_a_phone_that_was_verified_by_otp(): void
    {
        $this->post('/register', ['name' => 'Skipper', 'phone' => '9990003333'])
            ->assertSessionHasErrors('phone');

        $this->assertGuest();
    }

    public function test_an_otp_only_user_can_set_a_password_without_a_current_one(): void
    {
        $user = User::factory()->create(['phone' => '9990004444', 'password' => null]);

        $this->actingAs($user)
            ->patch('/account/password', ['password' => 'brand-new-pass1', 'password_confirmation' => 'brand-new-pass1'])
            ->assertRedirect(route('account.index'));

        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('brand-new-pass1', $user->fresh()->password));
    }

    public function test_a_user_with_a_password_still_needs_the_current_one_to_change_it(): void
    {
        $user = User::factory()->create(['password' => 'old-pass-123']);

        $this->actingAs($user)
            ->patch('/account/password', ['password' => 'brand-new-pass1', 'password_confirmation' => 'brand-new-pass1'])
            ->assertSessionHasErrors('current_password');
    }
}

/**
 * Test double: same shape as LogOtpGateway but captures the plaintext code
 * in a static instead of writing it to the log.
 */
class RecordingOtpGatewayForTests extends LogOtpGateway
{
    public static ?string $lastCode = null;

    public function send(string $phone, string $code): void
    {
        self::$lastCode = $code;
    }
}
