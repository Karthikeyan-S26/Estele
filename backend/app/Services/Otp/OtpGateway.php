<?php

namespace App\Services\Otp;

interface OtpGateway
{
    /**
     * Deliver a one-time code to a contact (a phone number for SMS channels,
     * an email address for the mail channel). Implementations decide how (SMS
     * provider API, mail transport, log, etc.) - callers never see the
     * transport.
     */
    public function send(string $contact, string $code): void;
}
