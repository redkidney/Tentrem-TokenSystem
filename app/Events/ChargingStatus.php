<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ChargingStatus implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $status;
    public $port;

    public function __construct($status, $port)
    {
        Log::info("ChargingStatus event created with status: {$status} and port: {$port}");
        $this->status = $status;
        $this->port = $port;
        Log::info("Event payload: " . json_encode($this));
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('charging-port'),
        ];
    }
}
