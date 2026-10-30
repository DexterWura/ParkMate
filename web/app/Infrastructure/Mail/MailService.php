<?php

namespace App\Infrastructure\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail as LaravelMail;

class MailService
{
    public function send(string $to, Mailable $mailable): void
    {
        LaravelMail::to($to)->send($mailable);
    }

    public function queue(string $to, Mailable $mailable): void
    {
        LaravelMail::to($to)->queue($mailable);
    }
}
