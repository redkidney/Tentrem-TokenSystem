<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MonitorUpdate implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $port;
    public $status;
    public $token;
    public $duration;
    public $remainingTime;

    /**
     * Create a new event instance.
     *
     * @param int $port
     * @param string $token
     * @param int $duration
     * @param int $remainingTime
     */
    public function __construct($status, $port, $token, $duration, $remainingTime)
    {
        $this->status = $status;
        $this->port = $port;
        $this->token = $token;
        $this->duration = $duration;
        $this->remainingTime = $remainingTime;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new Channel('monitor-update');
    }

    /**
     * Define the event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'MonitorUpdate';
    }
}
