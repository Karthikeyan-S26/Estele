<?php

namespace App\Services\WhatsApp;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Real WhatsApp delivery via Twilio's Messages API, called directly over HTTP
 * (same "no SDK dependency, Http facade + Basic Auth" shape as every other
 * gateway in this codebase — see TwilioOtpGateway/ShiprocketService).
 *
 * Twilio WhatsApp uses the same Messages resource as SMS; the address is
 * prefixed `whatsapp:` on both To and From. Numbers are normalized to Indian
 * E.164 (last 10 digits + +91) exactly like the OTP gateway.
 *
 * isConfigured() only proves the three values are present, not that delivery
 * actually works — a real end-to-end send to an Indian number is still needed
 * to confirm the WhatsApp number is registered/enabled with the provider.
 */
class TwilioWhatsAppSender implements WhatsAppSender
{
    public function isConfigured(): bool
    {
        return filled(config('services.twilio.sid'))
            && filled(config('services.twilio.token'))
            && filled(config('services.whatsapp.from'));
    }

    public function send(string $phone, string $message, array $context = []): void
    {
        $cleanPhone = substr(preg_replace('/\D/', '', $phone), -10);
        $to = 'whatsapp:+91'.$cleanPhone;
        $from = 'whatsapp:'.config('services.whatsapp.from');
        $sid = config('services.twilio.sid');

        try {
            $response = Http::asForm()
                ->withBasicAuth($sid, config('services.twilio.token'))
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                    'To' => $to,
                    'From' => $from,
                    'Body' => Str::limit($message, 1024),
                ]);
        } catch (ConnectionException $exception) {
            Log::error('Twilio WhatsApp gateway request failed', [
                'exception' => $exception::class,
                'phone_suffix' => substr($cleanPhone, -4),
            ]);

            return;
        }

        if (! $response->successful()) {
            Log::error('Twilio WhatsApp gateway returned a non-success response', [
                'status' => $response->status(),
                'body' => Str::limit($response->body(), 300),
                'phone_suffix' => substr($cleanPhone, -4),
            ]);
        }
    }
}