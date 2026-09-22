<?php

namespace App\Services\Otp;

interface OtpVerifier
{
    public function verify(string $contact, string $code): bool;
}
