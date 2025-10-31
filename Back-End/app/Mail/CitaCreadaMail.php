<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CitaCreadaMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload; // alumno, docente, fecha_cita, estado, modalidad, motivo
    }

    public function build()
    {
        $subject = 'Tu cita fue registrada – ' . ($this->payload['fecha_pretty'] ?? '');
        return $this->subject($subject)
            ->markdown('emails.citas.creada', $this->payload);
    }
}
