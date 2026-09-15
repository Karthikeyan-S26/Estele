<?php

namespace Tests\Feature;

use App\Models\OtpCode;
use App\Models\User;
use App\Services\Otp\OtpManager;
use App\Services\Otp\TwilioOtpGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * TwilioOtpGateway backed by the Twilio Verify API (Verifications /
 * VerificationCheck over Http::fake), plus the OtpManager wiring that lets it
 * verify codes remotely while VAS / Log / Mail gateways keep the local-hash
 * path. Credentials used here are fake test values — the real ones only ever
 * live in the local .env, never in tests, fixtures or log output.
 */
class VerifyTwilioOtpGatewayTest extends TestCase
{
    use RefreshDatabase;

    private const SID = 'AC_test_sid';

    private const TOKEN = 'test_token_that_must_never_be_logged';

    private const SERVICE_SID = 'VA_test_service_sid';

    private const VERIFICATIONS_URL = 'https://verify.twilio.com/v2/Services/VA_test_service_sid/Verifications';

    private const VERIFICATION_CHECK_URL = 'https://verify.twilio.com/v2/Services/VA_test_service_sid/VerificationCheck';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.sms.driver' => 'twilio']);
        config(['services.twilio.sid' => self::SID]);
        config(['services.twilio.token' => self::TOKEN]);
        config(['services.twilio.verify_service_sid' => self::SERVICE_SID]);
    }

    public function test_gateway_is_configured_with_the_service_sid_and_without_any_phone_number(): void
    {
        $this->assertTrue(app(TwilioOtpGateway::class)->isConfigured());
        $this->assertNull(config('services.twilio.from'), 'Verify must not require a Twilio sender number.');
    }

    public function test_send_posts_a_verification_via_twilio_verify_with_sms_channel_and_no_from_number(): void
    {
        Http::fake([
            self::VERIFICATIONS_URL => Http::response(['status' => 'pending', 'sid' => 'VE_test'], 201),
        ]);

        app(TwilioOtpGateway::class)->send('9876543210', '123456');

        Http::assertSent(fn ($request) => $request->url() === self::VERIFICATIONS_URL
            && $request->method() === 'POST'
            && $request['To'] === '+919876543210'
            && $request['Channel'] === 'sms'
            && ! array_key_exists('From', $request->data())
            && $request->hasHeader('Authorization', 'Basic '.base64_encode(self::SID.':'.self::TOKEN)));
    }

    public function test_a_failed_otp_send_logs_an_error_without_throwing(): void
    {
        Http::fake([
            self::VERIFICATIONS_URL => Http::response(['code' => 20404, 'message' => 'Not found'], 404),
        ]);
        Log::spy();

        app(TwilioOtpGateway::class)->send('9876543210', '123456');

        Log::shouldHaveReceived('error')->withArgs(
            fn ($message, $context) => str_contains($message, 'non-success response')
                && ! $this->contextMentionsToken($context)
        );
    }

    public function test_a_connection_failure_on_send_is_logged_without_the_token(): void
    {
        Http::fake([
            self::VERIFICATIONS_URL => fn () => throw new ConnectionException('Connection refused'),
        ]);
        Log::spy();

        app(TwilioOtpGateway::class)->send('9876543210', '123456');

        Log::shouldHaveReceived('error')->withArgs(
            fn ($message, $context) => str_contains($message, 'request failed')
                && ! $this->contextMentionsToken($context)
        );
    }

    public function test_verify_returns_true_and_consumes_the_code_when_twilio_approves(): void
    {
        Http::fake([
            self::VERIFICATIONS_URL => Http::response(['status' => 'pending'], 201),
            self::VERIFICATION_CHECK_URL => Http::response(['status' => 'approved'], 200),
        ]);

        $manager = app(OtpManager::class);
        $manager->issue('9876543210');

        $this->assertTrue($manager->verify('9876543210', '424242'));
        $this->assertNotNull(OtpCode::where('phone', '9876543210')->first()->consumed_at);
    }

    public function test_verify_rejects_a_wrong_code_without_consuming_it(): void
    {
        Http::fake([
            self::VERIFICATIONS_URL => Http::response(['status' => 'pending'], 201),
            self::VERIFICATION_CHECK_URL => Http::response(['status' => 'pending'], 200),
        ]);

        $manager = app(OtpManager::class);
        $manager->issue('9876543210');

        $this->assertFalse($manager->verify('9876543210', '000000'));
        $this->assertNull(OtpCode::where('phone', '9876543210')->first()->consumed_at);
    }

    public function test_verify_returns_false_when_twilio_check_returns_an_api_error(): void
    {
        Http::fake([
            self::VERIFICATIONS_URL => Http::response(['status' => 'pending'], 201),
            self::VERIFICATION_CHECK_URL => Http::response(['code' => 20404, 'message' => 'Not found'], 404),
        ]);
        Log::spy();

        $manager = app(OtpManager::class);
        $manager->issue('9876543210');

        $this->assertFalse($manager->verify('9876543210', '424242'));
        $this->assertNull(OtpCode::where('phone', '9876543210')->first()->consumed_at);

        Log::shouldHaveReceived('error')->withArgs(
            fn ($message, $context) => str_contains($message, 'non-success response')
                && ! $this->contextMentionsToken($context)
        );
    }

    public function test_a_connection_failure_on_verify_rejects_safely_without_the_token(): void
    {
        Http::fake([
            self::VERIFICATIONS_URL => Http::response(['status' => 'pending'], 201),
            self::VERIFICATION_CHECK_URL => fn () => throw new ConnectionException('Connection refused'),
        ]);
        Log::spy();

        $manager = app(OtpManager::class);
        $manager->issue('9876543210');

        $this->assertFalse($manager->verify('9876543210', '424242'));

        Log::shouldHaveReceived('error')->withArgs(
            fn ($message, $context) => str_contains($message, 'check request failed')
                && ! $this->contextMentionsToken($context)
        );
    }

    public function test_website_otp_login_flow_still_runs_through_the_same_shared_otp_system(): void
    {
        Http::fake([
            self::VERIFICATIONS_URL => Http::response(['status' => 'pending'], 201),
            self::VERIFICATION_CHECK_URL => Http::response(['status' => 'approved'], 200),
        ]);

        $user = User::factory()->create(['phone' => '9876543210']);

        $this->post('/login', ['phone' => '9876543210'])->assertRedirect(route('login.verify'));
        $this->post('/login/verify', ['code' => '424242'])->assertRedirect(route('account.index'));

        $this->assertAuthenticatedAs($user);

        Http::assertSent(fn ($request) => $request->url() === self::VERIFICATIONS_URL
            && array_key_exists('To', $request->data())
            && ! array_key_exists('From', $request->data()));
        Http::assertSent(fn ($request) => $request->url() === self::VERIFICATION_CHECK_URL);
    }

    private function contextMentionsToken(mixed $context): bool
    {
        return str_contains(json_encode($context), self::TOKEN);
    }
}