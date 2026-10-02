<?php

namespace App\Services\Otp;

use App\Mail\OtpMail;
use Illuminate\Support\Facades\Mail;

/**
 * Email OTP delivery for the 'email' channel: delegates to Laravel's own mail
 * transport, so which backend actually delivers the code is decided entirely
 * by MAIL_MAILER (smtp/log/array/...) — the same "framework decides the
 * transport, the gateway never sees it" split the site already relies on for
 * reset-password emails. In dev with MAIL_MAILER=log the code is written to
 * the Laravel log (mirroring LogOtpGateway's posture); in production with a
 * real SMTP/mail provider configured it is genuinely delivered. No
 * isConfigured() gate needed: an unset mailer simply falls back to the log
 * transport like every other email this app sends.
 */
class MailOtpGateway implements OtpGateway
{
    public function send(string $contact, string $code): void
    {
        Mail::to($contact)->send(new OtpMail($code));
    }
}