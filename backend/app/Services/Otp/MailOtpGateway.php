<?php

namespace App\Services\Otp;

class MailOtpGateway implements OtpGateway
{
    public function send(string $contact, string $code): bool
    {
        \Illuminate\Support\Facades\Mail::to($contact)->send(new \App\Mail\OtpMail($code));

        return true;
    }
}
