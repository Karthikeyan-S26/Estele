<?php

namespace App\Services\Otp;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Real SMS OTP delivery and verification via Twilio's Verify API, called
 * directly over HTTP (same "no SDK dependency, Http facade + Basic Auth"
 * shape as every other gateway in this codebase — see VasMultimediaOtpGateway
 * /ShiprocketService) rather than pulling in twilio/sdk for two endpoints.
 *
 * Backed by the Verify API (docs: twilio.com/docs/verify/api/verification and
 * .../verification-check), not the raw Messages API: Verify owns the whole
 * lifecycle — it generates the code, delivers it over Twilio's carrier-routed
 * SMS, and validates it back through VerificationCheck. That removes the old
 * sender-number requirement (the previous Messages integration needed a
 * TWILIO_PHONE_NUMBER to put in the From field, and assumed a US long code
 * that Indian carriers commonly filtered for +91 A2P), so isConfigured() and
 * send() never touch a phone number. Because Twilio generates the code, the
 * plaintext this app's OtpManager::issue() generates is never transmitted;
 * when this gateway is active OtpManager delegates the code check to the
 * OtpVerifier contract here instead of comparing the local hash.
 *
 * Failure handling never echoes the response body into the logs: the request
 * carries the account token in its HTTP Basic Auth header, so a non-success
 * response is logged as status + Twilio error code only, never the raw
 * payload. A wrong user code (Twilio returns `pending` for that) and a
 * transport failure both stay false so nothing crashes the login flow.
 */
class TwilioOtpGateway implements OtpGateway, OtpVerifier
{
    public function isConfigured(): bool
    {
        return filled(config('services.twilio.sid'))
            && filled(config('services.twilio.token'))
            && filled(config('services.twilio.verify_service_sid'));
    }

    public function send(string $contact, string $code): void
    {
        $to = $this->toE164($contact);

        try {
            $response = $this->client()->post("{$this->serviceBaseUrl()}/Verifications", [
                'To' => $to,
                'Channel' => 'sms',
            ]);
        } catch (ConnectionException $exception) {
            Log::error('Twilio Verify SMS gateway request failed', [
                'exception' => $exception::class,
                'phone_suffix' => substr($to, -4),
            ]);

            return;
        }

        if (! $response->successful()) {
            Log::error('Twilio Verify SMS gateway returned a non-success response', [
                'status' => $response->status(),
                'error_code' => $response->json('code'),
                'phone_suffix' => substr($to, -4),
            ]);
        }
    }

    /**
     * Asks Twilio Verify whether $code is the code it sent for $contact.
     * Anything other than an `approved` status counts as "not verified": a
     * wrong code comes back as `pending`, an exhausted attempt cap as
     * `max_attempts_reached`, and an expired/already-approved verification
     * makes the endpoint 404 — all logged without the response body (the
     * auth token rides on the request, never in the response we echo).
     */
    public function verify(string $contact, string $code): bool
    {
        $to = $this->toE164($contact);

        try {
            $response = $this->client()->post("{$this->serviceBaseUrl()}/VerificationCheck", [
                'To' => $to,
                'Code' => $code,
            ]);
        } catch (ConnectionException $exception) {
            Log::error('Twilio Verify check request failed', [
                'exception' => $exception::class,
                'phone_suffix' => substr($to, -4),
            ]);

            return false;
        }

        if (! $response->successful()) {
            Log::error('Twilio Verify check request returned a non-success response', [
                'status' => $response->status(),
                'error_code' => $response->json('code'),
                'phone_suffix' => substr($to, -4),
            ]);

            return false;
        }

        if ($response->json('status') !== 'approved') {
            Log::info('Twilio Verify code check rejected the submitted code', [
                'status' => $response->json('status'),
                'phone_suffix' => substr($to, -4),
            ]);

            return false;
        }

        return true;
    }

    private function client(): PendingRequest
    {
        return Http::asForm()
            ->withBasicAuth(config('services.twilio.sid'), config('services.twilio.token'))
            ->connectTimeout(5)
            ->timeout(10);
    }

    private function serviceBaseUrl(): string
    {
        return 'https://verify.twilio.com/v2/Services/'.config('services.twilio.verify_service_sid');
    }

    private function toE164(string $contact): string
    {
        return '+91'.substr(preg_replace('/\D/', '', $contact), -10);
    }
}