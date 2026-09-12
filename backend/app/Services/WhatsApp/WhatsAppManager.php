<?php

namespace App\Services\WhatsApp;

/**
 * Single entry point for business WhatsApp notifications (sell buy-back,
 * order status, wallet credits…). Mirrors OtpManager/PaymentManager's role
 * in this codebase — one call site, and swapping the provider is a
 * constructor/config change, not a rewrite of every notification.
 *
 * WhatsApp comes from the same Twilio account as the phone OTPs when
 * WHATSAPP_DRIVER=twilio and the keys + FROM number are set; otherwise it
 * falls back to the log sender (same "isConfigured() ? real : fallback"
 * shape as PaymentManager::isOnlinePaymentEnabled() / ShippingManager's
 * flat-rate fallback). Logging is always the last resort.
 */
class WhatsAppManager
{
    public function __construct(
        private readonly LogWhatsAppSender $logSender,
        private readonly TwilioWhatsAppSender $twilioSender,
    ) {}

    private function gateway(): WhatsAppSender
    {
        return match (config('services.whatsapp.driver')) {
            'twilio' => $this->twilioSender->isConfigured() ? $this->twilioSender : $this->logSender,
            default => $this->twilioSender->isConfigured() ? $this->twilioSender : $this->logSender,
        };
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function send(string $phone, string $message, array $context = []): void
    {
        $this->gateway()->send($phone, $message, $context);
    }
}