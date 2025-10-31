<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CitaEstadoMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload; // alumno, docente, estado, fecha_cita, observaciones, actor
    }

    public function build()
    {
        $estado = $this->payload['estado'] ?? 'Actualizada';
        $subject = "Cita {$estado} – " . ($this->payload['fecha_pretty'] ?? '');
        return $this->subject($subject)
            ->markdown('emails.citas.estado', $this->payload);
    }
}
