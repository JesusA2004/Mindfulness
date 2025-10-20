<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $name;
    public string $emailPlain;
    public string $passwordPlain;

    public function __construct(string $name, string $emailPlain, string $passwordPlain)
    {
        $this->name = $name;
        $this->emailPlain = $emailPlain;
        $this->passwordPlain = $passwordPlain;
    }

    public function build()
    {
        return $this->subject('Tu acceso a Mindfulness')
            ->markdown('emails.user_password');
    }
}
