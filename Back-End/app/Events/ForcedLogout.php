<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class ForcedLogout implements ShouldBroadcast
{
    use SerializesModels;

    public string $userId;
    public string $reason;

    public function __construct($userId, $reason = 'new_login')
    {
        $this->userId = (string)$userId;
        $this->reason = $reason;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('user.' . $this->userId);
    }

    public function broadcastAs()
    {
        return 'ForcedLogout';
    }

    public function broadcastWith()
    {
        return ['reason' => $this->reason];
    }
}
