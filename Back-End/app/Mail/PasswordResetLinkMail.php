<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;

class PasswordResetLinkMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $name;
    public string $email;
    public string $resetUrl;

    /** CID calculado para <img src="{{ $logoCid }}"> en la vista (opcional) */
    public ?string $logoCid = null;

    public function __construct(string $name, string $email, string $resetUrl)
    {
        $this->name     = $name;
        $this->email    = $email;
        $this->resetUrl = $resetUrl;
    }

    public function build()
    {
        // 1) Preparar el CID del logo ANTES de renderizar la vista
        $logoPath = public_path('images/mail-logo.png');
        $cidRaw   = null;

        if (is_file($logoPath)) {
            $host   = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost';
            $cidRaw = 'logo-'.bin2hex(random_bytes(6)).'@'.$host; // (sin "cid:")
            $this->logoCid = 'cid:'.$cidRaw;                      // lo que irá en <img src="">
        }

        // 2) Usar plantilla Markdown con botón y estilos nativos de Mail::markdown
        $this->subject('Restablece tu contraseña')
             ->markdown('emails.password_reset_link', [
                 'name'     => $this->name,
                 'email'    => $this->email,
                 'resetUrl' => $this->resetUrl,
                 'logoCid'  => $this->logoCid,
             ]);

        // 3) Adjuntar el logo inline con el MISMO Content-ID que usa la vista
        if ($cidRaw) {
            $this->withSymfonyMessage(function (Email $email) use ($logoPath, $cidRaw) {
                $inline = DataPart::fromPath($logoPath)->asInline();
                $inline->setName('mail-logo.png');
                $inline->setContentId($cidRaw); // EXACTO al usado en la vista (sin "cid:")
                $email->addPart($inline);
            });
        }

        // También disponible como variables compartidas
        return $this->with([
            'logoCid'  => $this->logoCid,
            'resetUrl' => $this->resetUrl,
        ]);
    }
}
