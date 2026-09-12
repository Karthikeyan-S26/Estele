<?php

namespace App\Services\WhatsApp;

interface WhatsAppSender
{
    /**
     * Whether this sender has real credentials wired up. A configured sender
     * is preferred by WhatsAppManager; when nothing is configured the log
     * sender takes over (same "inactive until configured" posture as the
     * OTP gateways / Sentry DSN).
     */
    public function isConfigured(): bool;

    /**
     * Deliver a business message to a phone number. Implementations decide
     * how (provider API, log, etc.) — callers never see the transport.
     *
     * @param  array<string, mixed>  $context
     */
    public function send(string $phone, string $message, array $context = []): void;
}