<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class RecompensaCreada implements ShouldBroadcastNow
{
    /** @var array */
    public array $payload;

    /**
     * El payload debe incluir datos mínimos para que el front pinte la notificación.
     */
    public function __construct(array $payload = [])
    {
        $this->payload = $payload;
    }

    /**
     * Canal privado para TODOS los alumnos.
     * Solo usuarios con rol "estudiante" podrán autenticarse (ver routes/channels.php).
     */
    public function broadcastOn(): array
    {
        return [ new PrivateChannel('role.estudiante') ];
    }

    public function broadcastAs(): string
    {
        return 'RecompensaCreada';
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
