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

    /**
     * Create a new event instance.
     *
     * @param string $status
     * @param int $port
     * @param int|null $remaining_time Optional remaining time for resume events
     */

    public function __construct($status, $port, $remaining_time = null)
    {
        Log::info("ChargingStatus event created with status: {$status}, port: {$port}, and remaining_time: " . ($remaining_time ?? 'N/A'));
        $this->status = $status;
        $this->port = $port;
        $this->remaining_time = $remaining_time;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('charging-port'),
        ];
    }
}
