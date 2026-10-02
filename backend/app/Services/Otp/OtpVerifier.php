<?php

namespace App\Services\Otp;

/**
 * Implemented by OTP gateways that own the code-verification step remotely
 * instead of letting OtpManager compare the submitted code against the hash
 * of the code it generated in issue(). Today only TwilioOtpGateway does this,
 * backed by Twilio Verify's VerificationCheck (Twilio generates, delivers and
 * validates the code itself, so the locally generated code is never sent).
 *
 * When the active gateway implements this interface, OtpManager delegates the
 * "is this the right code?" decision to it; every other gateway keeps the
 * existing local-hash check untouched, and the plain OtpGateway contract is
 * unchanged so VAS Multimedia / Log / Mail gateways stay as-is.
 */
interface OtpVerifier
{
    public function verify(string $contact, string $code): bool;
}