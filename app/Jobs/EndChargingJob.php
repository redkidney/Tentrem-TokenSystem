<?php

namespace App\Jobs;

use App\Models\Port;
use App\Services\MqttPublishService;
use App\Http\Controllers\TokenController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class EndChargingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $token;
    protected $chargingPort;
    protected $sessionId;

    public function __construct($token, $chargingPort, $sessionId)
    {
        $this->token = $token;
        $this->chargingPort = $chargingPort;
        $this->sessionId = $sessionId;
    }

    public function handle(MqttPublishService $mqttService, TokenController $tokenController)
    {
        // Check if current session_id matches the cached one for this port
        $cachedSessionId = Cache::get("port_{$this->chargingPort}_session_id");

        if ($cachedSessionId !== $this->sessionId) {
            Log::info("(EndChargingJob) Stale job detected for port {$this->chargingPort}. Ignoring.");
            return;
        }

        Log::info("(EndChargingJob) Job started for token {$this->token} on port {$this->chargingPort}");

        $port = Port::find($this->chargingPort);
        if (!$port || $port->status !== 'running' || $port->current_token !== $this->token) {
            Log::warning("(EndChargingJob) Port {$this->chargingPort} not found, not running, or token mismatch", [
                'port_status' => $port ? $port->status : 'not found',
                'port_token' => $port ? $port->current_token : 'N/A',
                'job_token' => $this->token
            ]);
            return;
        }

        Log::info("(EndChargingJob) Port status validated, proceeding to end charging", [
            'port' => $port->id,
            'token' => $this->token,
            'start_time' => $port->start_time,
            'current_time' => now()
        ]);

        // Create request with necessary data
        $request = request()->merge([
            'token' => $this->token,
            'port' => $this->chargingPort
        ]);

        $response = $tokenController->endCharging($request, $this->chargingPort);
        
        if (is_object($response) && method_exists($response, 'getData')) {
            $responseData = $response->getData();
            if (!$responseData->success) {
                Log::error("(EndChargingJob) Failed to end charging session via controller", [
                    'response' => $responseData
                ]);
                return;
            }
        } else {
            Log::error("(EndChargingJob) Unexpected response format from controller");
            return;
        }

        // Stop physical charging
        Log::info("(EndChargingJob) Database updated, sending MQTT stop command");
        
        $mqttService->connect();
        $message = json_encode(['action' => 'stop']);
        $mqttService->publish("charging/port{$this->chargingPort}", $message);
        
        Log::info("(EndChargingJob) Charging session ended successfully");
    }
}
