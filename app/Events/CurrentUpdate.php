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

class CurrentUpdate implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $port;
    public $current;

    /**
     * Create a new event instance.
     *
     * @param int $port
     * @param float $current
     */

     public function __construct($port, $current)
     {
         $this->port = $port;
         $this->current = $current;
     }
 
     public function broadcastOn()
     {
         return new Channel("current-port.{$this->port}");
     }
 
     public function broadcastAs()
     {
         return 'CurrentUpdate';
     }
}
