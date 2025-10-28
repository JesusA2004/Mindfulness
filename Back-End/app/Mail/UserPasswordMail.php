<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;

class UserPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $name;
    public string $emailPlain;
    public string $passwordPlain;

    /** CID calculado para <img src="{{ $logoCid }}"> en la vista (opcional) */
    public ?string $logoCid = null;

    public function __construct(string $name, string $emailPlain, string $passwordPlain)
    {
        $this->name          = $name;
        $this->emailPlain    = $emailPlain;
        $this->passwordPlain = $passwordPlain;
    }

    public function build()
    {
        // URL del login (front primero; fallback al /login del back)
        $loginUrl = config('app.frontend_url')
            ? rtrim((string) config('app.frontend_url'), '/')
            : rtrim((string) config('app.url'), '/') . '/login';

        // === 1) Preparar CID del logo ANTES de renderizar la vista ===
        $logoPath = public_path('images/mail-logo.png');
        $cidRaw   = null;

        if (is_file($logoPath)) {
            $host   = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost';
            $cidRaw = 'logo-'.bin2hex(random_bytes(6)).'@'.$host; // (sin "cid:")
            $this->logoCid = 'cid:'.$cidRaw;                      // lo que irá en <img src="">
        }

        // === 2) Subject + vista (NO hay foto de perfil; solo logo opcional) ===
        $this->subject('Tu acceso a Mindora')
             ->markdown('emails.user_password', [
                 'loginUrl'      => $loginUrl,
                 'name'          => $this->name,
                 'emailPlain'    => $this->emailPlain,
                 'passwordPlain' => $this->passwordPlain, // se muestra sin doble-escape en la vista
                 'logoCid'       => $this->logoCid,
             ]);

        // === 3) Adjuntar el logo inline con el MISMO Content-ID que usa la vista ===
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
            'loginUrl' => $loginUrl,
        ]);
    }
}
