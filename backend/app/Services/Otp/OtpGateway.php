<?php

namespace App\Services\Otp;

interface OtpGateway
{
    /**
     * Deliver a one-time code to a phone number. Implementations decide how
     * (SMS provider API, log, etc.) — callers never see the transport.
     */
    public function send(string $phone, string $code): void;
}
