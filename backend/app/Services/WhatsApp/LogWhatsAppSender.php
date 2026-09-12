<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Log;

/**
 * Default WhatsApp sender: no provider wired up (built in-house per the same
 * launch-readiness posture as LogOtpGateway / the Sentry DSN gate) — logs the
 * message instead of delivering it. The whole notification flow that calls
 * WhatsAppManager stays fully real and working end-to-end today; it just needs
 * a real provider (Twilio WhatsApp / Gupshup / Interakt / etc.) dropped in as
 * a second WhatsAppSender implementation and pointed at from WhatsAppManager's
 * constructor when that happens — no business-logic changes required.
 *
 * The message is logged in full deliberately because, with no gateway
 * configured, this log line is the only record of what would have been sent.
 */
class LogWhatsAppSender implements WhatsAppSender
{
    public function isConfigured(): bool
    {
        return false;
    }

    public function send(string $phone, string $message, array $context = []): void
    {
        Log::info('WhatsApp message requested — no WhatsApp gateway configured, logging instead of sending', [
            'phone' => $phone,
            'message' => $message,
            'context' => $context,
        ]);
    }
}