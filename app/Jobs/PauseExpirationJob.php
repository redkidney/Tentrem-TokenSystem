<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Port;
use App\Events\ChargingStatus;
use App\Http\Controllers\TokenController;
use App\Events\MonitorUpdate;
use App\Services\MqttPublishService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class PauseExpirationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $port;

    public function __construct($port)
    {
        $this->port = $port;
    }

    public function handle(MqttPublishService $mqttService, TokenController $controller)
    {
        try {
            $port = Port::find($this->port);
            
            if ($port && $port->status === 'paused') {
                // Clear job-related cache first
                Cache::forget("port_{$this->port}_job_dispatched");
                Cache::forget("port_{$this->port}_session_id");

                // Send events before ending the charging
                event(new MonitorUpdate('pause_expired', $port->id, '', 0, 0));
                event(new ChargingStatus('charging_cancelled', $port->id, 0));

                // Stop hardware charging
                $mqttService->connect();
                $message = json_encode(['action' => 'stop']);
                $mqttService->publish("charging/port{$this->port}", $message);

                // Create a request object and end the charging
                $request = new Request([
                    'token' => $port->current_token,
                    'port' => $this->port
                ]);

                $controller->endCharging($request, $this->port);

                // Send pause expiry event after everything is done
                event(new ChargingStatus('pause_expired', $port->id));
                Log::info("Port {$port->id} charging ended due to pause expiration");
            }
        } catch (\Exception $e) {
            Log::error("PauseExpirationJob error for port {$this->port}: " . $e->getMessage());
        }
    }
}