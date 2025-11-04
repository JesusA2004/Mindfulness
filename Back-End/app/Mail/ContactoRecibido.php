<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContactoRecibido extends Mailable
{
    use Queueable, SerializesModels;

    public array $data;
    public array $meta;

    public function __construct(array $data, array $meta)
    {
        $this->data = $data;
        $this->meta = $meta;
    }

    public function build()
    {
        return $this->from(config('mail.from.address'), config('mail.from.name'))
            ->subject('Nuevo mensaje de contacto: '.$this->data['asunto'])
            ->view('emails.contacto.recibido');
    }
}
