<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ChargingStatus implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $status;
    public $port;
    public $remaining_time;
    public $pause_expiry;

    /**
     * Create a new event instance.
     *
     * @param string $status
     * @param int $port
     * @param int|null $remaining_time Optional remaining time for resume events
     */

     public function __construct($status, $port, $remaining_time = 0, $pause_expiry = null)
     {
         $this->status = $status;
         $this->port = $port;
         $this->remaining_time = $remaining_time;
         $this->pause_expiry = $pause_expiry;
     }

    public function broadcastOn(): array
    {
        return [
            new Channel('charging-port'),
        ];
    }

    public function broadcastAs()
    {
        return 'ChargingStatus';
    }
}
