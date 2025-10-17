<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class CitaEstadoCambiado implements ShouldBroadcastNow
{
    public string $destinatarioId;
    public array $payload;

    public function __construct(string $destinatarioId, array $payload)
    {
        $this->destinatarioId = $destinatarioId;
        $this->payload = $payload;
    }

    public function broadcastOn(): array
    {
        return [ new PrivateChannel('user.' . $this->destinatarioId) ];
    }

    public function broadcastAs(): string
    {
        return 'CitaEstadoCambiado';
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
