<x-admin-layout>
    <x-slot name="title">Charging Monitor</x-slot>

    @push('styles')
    <style>
        .pulse-animation {
            animation: pulse 1s infinite;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
    </style>
    @endpush

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 p-8">
        @foreach($ports as $port)
        <div 
            x-data="chargingMonitor({{ $port->id }}, {
                status: '{{ $port->status }}',
                token: '{{ $port->current_token }}',
                duration: {{ $port->duration ?? 0 }},
                pauseExpiry: {{ $port->pause_expiry ?? 'null' }},
                remainingTime: {{ $port->remaining_time ?? 0 }}
            })"  
            x-init="init()"
            :class="{ 'pulse-animation': status === 'paused' }"
            class="bg-white rounded-lg shadow-lg transition-all duration-300">

            <!-- Port Header -->
            <div class="p-6 border-b border-gray-100">
                <div class="flex justify-between items-center">
                    <h2 class="text-2xl font-bold">Port {{ $port->id }}</h2>
                    <div class="flex items-center gap-4">
                        <!-- Current Reading -->
                        <div class="px-3 py-1 rounded-full bg-blue-50 text-blue-700 font-medium">
                            <span x-text="currentAmp.toFixed(2)"></span>A
                        </div>
                        <!-- Status Badge -->
                        <div x-show="status !== 'idle'" 
                            :class="{
                                'bg-green-50 text-green-700': status === 'running',
                                'bg-yellow-50 text-yellow-700': status === 'paused'
                            }"
                            class="px-3 py-1 rounded-full text-sm font-medium"
                            x-text="status ? status.charAt(0).toUpperCase() + status.slice(1) : ''">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Active Session Content -->
            <div x-show="status !== 'idle'" class="p-6 space-y-6">
                <!-- Session Info -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <span class="text-gray-500 text-sm">Token</span>
                        <p class="font-semibold mt-1" x-text="token"></p>
                    </div>
                    <div>
                        <span class="text-gray-500 text-sm">Duration</span>
                        <p class="font-semibold mt-1" x-text="`${duration} min`"></p>
                    </div>
                </div>

                <!-- Status Message -->
                <div class="p-4 rounded-lg" 
                     :class="{
                         'bg-green-50 border border-green-200': status === 'running',
                         'bg-yellow-50 border border-yellow-200': status === 'paused'
                     }">
                    <p class="font-medium" :class="{
                        'text-green-700': status === 'running',
                        'text-yellow-700': status === 'paused'
                    }" x-text="status === 'running' ? 'Charging in progress' : 'Charging paused'"></p>
                </div>

                <!-- Timer Circle -->
                <div class="flex justify-center">
                    <div class="relative w-48 h-48">
                        <svg class="w-full h-full -rotate-90">
                            <circle class="text-gray-100" 
                                    stroke="currentColor"
                                    stroke-width="8"
                                    fill="transparent"
                                    r="90"
                                    cx="96"
                                    cy="96"/>
                            <circle class="transition-all duration-300" 
                                    stroke="currentColor"
                                    stroke-width="8"
                                    fill="transparent"
                                    r="90"
                                    cx="96"
                                    cy="96"
                                    stroke-linecap="round"
                                    :class="status === 'running' ? 'text-green-500' : 'text-yellow-500'"
                                    :stroke-dasharray="circumference"
                                    :stroke-dashoffset="strokeDashoffset"/>
                        </svg>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <div class="text-center">
                                <p class="text-4xl font-bold" x-text="formatTime(remainingTime)"></p>
                                <p class="text-sm text-gray-500">Remaining</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pause Expiry Warning -->
                <div x-show="status === 'paused'" 
                     class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <p class="text-yellow-700 font-medium">
                        Pause expires in: <span x-text="formatTime(pauseTimeLeft)"></span>
                    </p>
                </div>

                <!-- Control Button -->
                <div class="flex justify-center">
                    <button @click="handleCancel({{ $port->id }})"
                            class="px-6 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors">
                        Cancel Session
                    </button>
                </div>
            </div>

            <!-- Idle State -->
            <div x-show="status === 'idle'" class="p-6">
                <div class="text-center text-gray-500">
                    <p class="text-lg">No active charging session</p>
                    <p class="text-sm mt-2">Current Draw: <span x-text="currentAmp.toFixed(2)"></span>A</p>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    @push('scripts')
    <script>
        function chargingMonitor(port, initialData) {
            return {
                status: initialData.status || 'idle',
                token: initialData.token || '',
                duration: initialData.duration || 0,
                remainingTime: initialData.remainingTime || 0,
                currentAmp: 0,
                interval: null,
                pauseTimeLeft: initialData.pauseExpiry || 0,
                pauseTimer: null,

                circumference: 2 * Math.PI * 90,
                
                get strokeDashoffset() {
                    return this.circumference * (1 - (this.remainingTime / (this.duration * 60)));
                },

                init() {
                    console.log('Initial state:', this.status, initialData);
                    
                    // Start countdown if status is running
                    if (this.status === 'running' && this.remainingTime > 0) {
                        this.startCountdown();
                    }
                    // Start pause timer if status is paused
                    else if (this.status === 'paused' && this.pauseTimeLeft > 0) {
                        this.startPauseExpiry();
                    }
                    
                    this.setupEventListeners();
                },
                
                setupEventListeners() {
                    Echo.channel(`charging-port`)
                        .listen('.ChargingStatus', (e) => {
                            console.log('ChargingStatus received:', e);
                            if (e.port === port) {
                                this.handleStatusEvent(e);
                            }
                        });

                    Echo.channel('monitor-update')
                        .listen('.MonitorUpdate', (e) => {
                            console.log('MonitorUpdate received:', e);
                            if (e.port === port) {
                                this.handleMonitorUpdate(e);
                            }
                        });

                    Echo.channel(`current-port.${port}`)
                        .listen('.CurrentUpdate', e => {
                            this.currentAmp = e.current || 0;
                        });
                },

                handleMonitorUpdate(event) {
                    console.log('Processing MonitorUpdate:', event);
                    
                    switch (event.status) {
                        case 'charging_started':
                        case 'charging_resumed':
                            this.token = event.token;
                            this.duration = event.duration;  // Make sure duration is set
                            this.startCharging(event.remainingTime);
                            break;

                        case 'charging_cancelled':
                            this.resetState();
                            break;

                        default:
                            if (event.remainingTime === 0 && event.token === '') {
                                this.resetState();
                            } else {
                                this.token = event.token;
                                // Only update duration if it's provided and non-zero
                                if (event.duration) {
                                    this.duration = event.duration;
                                }
                                this.remainingTime = event.remainingTime;
                            }
                    }
                },

                handleStatusEvent(event) {
                    const data = typeof event.data === 'string' ? JSON.parse(event.data) : event;
                    console.log('Processing ChargingStatus:', data);

                    switch (data.status) {
                        case 'charging_paused':
                            // Keep the duration when pausing
                            const currentDuration = this.duration;
                            this.handlePause(data.remaining_time, data.pause_expiry);
                            this.duration = currentDuration;  // Restore duration after pause
                            break;
                        case 'charging_ended':
                            this.resetState();
                            break;
                        case 'pause_expired':
                            this.handlePauseExpiry();
                            break;
                    }
                },

                startCharging(remainingTime) {
                    this.status = 'running';
                    this.remainingTime = remainingTime;
                    this.startCountdown();
                },

                handlePause(remainingTime, pauseExpiry) {
                    const currentDuration = this.duration; // Store current duration
                    this.status = 'paused';
                    this.remainingTime = remainingTime;
                    this.pauseTimeLeft = pauseExpiry;
                    this.duration = currentDuration; // Restore duration
                    if (this.interval) clearInterval(this.interval);
                    this.startPauseExpiry();
                },

                resumeCharging(remainingTime) {
                    const currentDuration = this.duration; // Store current duration
                    this.status = 'running';
                    this.remainingTime = remainingTime;
                    this.duration = currentDuration; // Restore duration
                    if (this.pauseTimer) clearInterval(this.pauseTimer);
                    this.pauseTimeLeft = 0;
                    this.startCountdown();
                },

                handlePauseExpiry() {
                    if (this.pauseTimer) clearInterval(this.pauseTimer);
                    this.resetState();
                },

                startCountdown() {
                    if (this.interval) clearInterval(this.interval);
                    
                    this.interval = setInterval(() => {
                        if (this.remainingTime > 0) {
                            this.remainingTime--;
                            if (this.remainingTime === 0) {
                                this.handleSessionEnd();
                            }
                        }
                    }, 1000);
                },

                startPauseExpiry() {
                    if (this.pauseTimer) clearInterval(this.pauseTimer);
                    
                    this.pauseTimer = setInterval(() => {
                        if (this.pauseTimeLeft > 0) {
                            this.pauseTimeLeft--;
                            if (this.pauseTimeLeft === 0) {
                                this.handlePauseExpiry();
                            }
                        }
                    }, 1000);
                },

                handleSessionEnd() {
                    clearInterval(this.interval);
                    setTimeout(() => this.resetState(), 2000);
                },

                handleCancel(portId) {
                    if (!confirm('Are you sure you want to cancel this charging session?')) return;
                    
                    fetch(`charging/${portId}/cancel`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });
                },

                resetState() {
                    if (this.interval) clearInterval(this.interval);
                    if (this.pauseTimer) clearInterval(this.pauseTimer);
                    this.status = 'idle';
                    this.token = '';
                    this.remainingTime = 0;
                    this.pauseTimeLeft = 0;
                    this.interval = null;
                    this.pauseTimer = null;
                },

                formatTime(seconds) {
                    if (!seconds || isNaN(seconds)) return "0:00";
                    const mins = Math.floor(seconds / 60);
                    const secs = Math.floor(seconds % 60);
                    return `${mins}:${secs.toString().padStart(2, '0')}`;
                }
            };
        }
    </script>
    @endpush
</x-admin-layout>