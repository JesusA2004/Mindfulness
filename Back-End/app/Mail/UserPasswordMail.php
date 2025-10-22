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

    /** CID para usar en el <img src> del header */
    public ?string $logoCid = null;

    public function __construct(string $name, string $emailPlain, string $passwordPlain)
    {
        $this->name          = $name;
        $this->emailPlain    = $emailPlain;
        $this->passwordPlain = $passwordPlain;
    }

    public function build()
    {
        // URL del login (front primero; si no, /login del back)
        $loginUrl = config('app.frontend_url')
            ? rtrim(config('app.frontend_url'), '/')
            : rtrim(config('app.url'), '/') . '/login';

        // === 1) Preparar CID ANTES de renderizar la vista ===
        $logoPath = public_path('images/mail-logo.png');
        $cidRaw   = null;

        if (is_file($logoPath)) {
            $host   = parse_url(config('app.url'), PHP_URL_HOST) ?: 'localhost';
            $cidRaw = 'logo-' . bin2hex(random_bytes(6)) . '@' . $host; // (sin "cid:")
            $this->logoCid = 'cid:' . $cidRaw;                           // para <img>
        }

        // === 2) Render con variables (logoCid ya calculado) ===
        $this->subject('Tu acceso a Mindora')
             ->markdown('emails.user_password', [
                 'loginUrl'      => $loginUrl,
                 'name'          => $this->name,
                 'emailPlain'    => $this->emailPlain,
                 'passwordPlain' => $this->passwordPlain,
                 'logoCid'       => $this->logoCid,
             ]);

        // === 3) Embebido inline usando EXACTAMENTE el mismo Content-ID ===
        if ($cidRaw) {
            $this->withSymfonyMessage(function (Email $email) use ($logoPath, $cidRaw) {
                $inline = DataPart::fromPath($logoPath)->asInline();
                $inline->setName('mail-logo.png');
                $inline->setContentId($cidRaw); // <= MISMO CID que en el HTML (sin "cid:")
                $email->addPart($inline);
            });
        }

        // (opcional) compartir variables a otras parciales
        return $this->with([
            'logoCid'  => $this->logoCid,
            'loginUrl' => $loginUrl,
        ]);
    }
}
