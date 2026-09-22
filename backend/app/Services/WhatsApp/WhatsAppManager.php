<?php

namespace App\Services\WhatsApp;

/**
 * Single entry point for business WhatsApp notifications (order status,
 * wallet credits…). Mirrors OtpManager/PaymentManager's role in this
 * codebase — one call site, and swapping the provider is a
 * constructor/config change, not a rewrite of every notification.
 *
 * The concrete sender comes from the container binding in
 * AppServiceProvider (LogWhatsAppGateway until a provider is configured,
 * CloudApiWhatsAppGateway once WHATSAPP_PROVIDER=cloud_api and its three
 * credentials are set) — same "configured ? real : fallback" shape as
 * PaymentManager::isOnlinePaymentEnabled() / ShippingManager's flat-rate
 * fallback elsewhere in this codebase. Logging is always the last resort.
 */
class WhatsAppManager
{
    public function __construct(private readonly WhatsAppGateway $gateway) {}

    /**
     * @param  array<string, mixed>  $context
     */
    public function send(string $phone, string $message, array $context = []): void
    {
        $this->gateway->send($phone, $message);
    }
}