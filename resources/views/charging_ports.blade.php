<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer - Charging Ports</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite('resources/css/app.css')
    @vite('resources/js/app.js')
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <style>
        label, .text-sm {
            font-size: 1.1em;
        }
        
        body {
            font-size: 1.3em;
        }

        .shake {
            animation: shake 0.3s;
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .paused {
            color: #ff8800; /* Orange color to indicate pause */
        }
        .pulse-animation {
            animation: pulse 1s infinite;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .grid-container {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
            padding: 2rem;
        }

        @media (min-width: 768px) {
            .grid-container {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        .header {
            grid-column: span 2;
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .logo {
            width: 80px;
            height: 80px;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col justify-center p-4">

    <div class="header">
        <img src="{{ asset('images/Asset-1.png') }}" alt="Logo" class="logo mx-auto" style="width: 200px; max-width: 100%; height: auto; margin-bottom: 1.5rem;">
        <h1 class="text-4xl font-bold text-center tracking-wide">
            {{ __('EV Charging Station') }}
        </h1>
    </div>

    <div id="toast-container" class="fixed top-5 right-5 z-50"></div>

    <!-- Charging Ports Grid Layout -->
    <div class="grid-container">

        @foreach([1, 2] as $port)
        <div id="port{{ $port }}" x-data="chargingStation({{ $port }}, '{{ $ports[$port-1]->status }}', '{{ $ports[$port-1]->start_time }}', '{{ $ports[$port-1]->end_time }}', {{ $ports[$port-1]->duration }})" x-init="init()" class="bg-white shadow-lg rounded-lg p-6">
            <h1 class="text-3xl font-bold text-center mb-4" :class="{'bg-gradient-to-r from-blue-500 to-green-500 text-white p-2 rounded': isCharging}">Station {{ $port }}</h1>

            <!-- Token Form -->
            <form @submit.prevent="handleFormSubmit" x-show="!isValidated && !isCharging && !isPaused" class="space-y-4">
                <label :for="'token'+{{ $port }}" class="block text-lg font-medium text-gray-700">Enter Token:</label>
                <input :id="'token'+{{ $port }}" type="text" x-model="token" placeholder="5-character token" required maxlength="5"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <p class="text-base text-gray-500">Characters remaining: <span x-text="5 - token.length"></span></p>
                <div class="grid grid-cols-3 gap-4">
                    @foreach(['1', '2', '3', '4', '5', '6', '7', '8', '9', 'Clear', '0', 'Submit'] as $button)
                        @if($button === 'Clear')
                            <button type="button" @click="clearToken()" 
                                    class="bg-red-200 py-2 rounded-md text-center col-span-1 hover:bg-red-300 active:bg-red-400 transition duration-200 focus:outline-none focus:ring-2 focus:ring-red-500">
                                Clear
                            </button>
                        @elseif($button === 'Submit')
                            <button type="submit" :disabled="token.length !== 5" 
                                    class="bg-green-500 text-white py-2 rounded-md col-span-1 disabled:bg-gray-300 hover:bg-green-600 active:bg-green-700 transition duration-300 focus:outline-none focus:ring-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed">
                                Submit
                            </button>
                        @else
                            <button type="button" @click="addToToken('{{ $button }}')" 
                                    class="bg-gray-200 py-2 rounded-md text-center hover:bg-gray-400 active:bg-gray-500 transition duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                {{ $button }}
                            </button>
                        @endif
                    @endforeach
                </div>
            </form>

            <!-- Start Charging Button (Hidden when paused) -->
            <div x-show="isValidated && !isCharging && !isPaused" class="text-center space-y-4">
                <p class="text-gray-700">Please plug in the charger for Port {{ $port }} and press start.</p>
                <button @click="handleStartCharging" class="w-full bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600 transition duration-300">Start Charging</button>
            </div>

            <!-- Charging/Paused Status Message -->
            <div x-show="isCharging || isPaused" class="mt-4 p-3 border rounded" :class="{'bg-green-100 border-green-400 text-green-700': isCharging, 'bg-orange-100 border-orange-400 text-orange-700': isPaused}">
                <p x-text="isCharging ? 'Charging in progress' : 'Charging paused'" class="text-lg font-semibold"></p>
            </div>

            <!-- Countdown Timer -->
            <div x-show="isCharging || isPaused" class="text-center space-y-4 mt-4">
                <div class="relative w-48 h-48 mx-auto">
                    <svg class="w-full h-full" viewBox="0 0 100 100">
                        <circle class="text-gray-200 stroke-current" stroke-width="8" cx="50" cy="50" r="40" fill="transparent" />
                        <circle class="text-green-500 stroke-current" stroke-width="8" stroke-linecap="round"
                                cx="50" cy="50" r="40" fill="transparent"
                                :stroke-dasharray="2 * Math.PI * 40"
                                :stroke-dashoffset="(2 * Math.PI * 40) * (1 - remainingTime / (duration * 60))" />
                    </svg>                    
                    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 text-center">
                        <p class="text-4xl font-bold" x-text="formatTime(remainingTime)"></p>
                        <p class="text-base text-gray-500">Remaining</p>
                    </div>
                </div>
            </div>
        </div>
        @endforeach

    </div>

    <script>
        function chargingStation(port, initialStatus = 'idle', startTime = null, endTime = null, duration) {
            return {
                token: '',
                isValidated: false,
                isCharging: initialStatus === 'running',
                isPaused: initialStatus === 'paused',
                remainingTime: 0,
                duration: duration,
                interval: null,
                isStarting: false,

                init() {
                    if (this.isCharging) {
                        this.isValidated = true;
                        this.calculateRemainingTime(startTime, endTime);
                    } else if (this.isPaused) {
                        this.remainingTime = this.duration * 60;
                        this.displayPausedTimer();
                    }

                    window.Echo.channel('charging-port')
                        .listen('ChargingStatus', (e) => {
                            if (e.status === 'charging_started' && e.port === port) {
                                if (!this.isCharging) this.handleStartCharging();
                            } else if (e.status === 'charging_paused' && e.port === port) {
                                this.pauseCountdown(e.remaining_time);
                            } else if (e.status === 'charging_resumed' && e.port === port) {
                                this.resumeCountdown(e.remaining_time);
                            }
                        });
                },

                calculateRemainingTime(startTime, endTime) {
                    const now = new Date();
                    const start = new Date(startTime);
                    const end = new Date(endTime);

                    const totalDuration = (end - start) / 1000;
                    const elapsedTime = (now - start) / 1000;
                    this.remainingTime = Math.max(0, Math.floor(totalDuration - elapsedTime));
                    this.duration = totalDuration / 60;

                    if (this.remainingTime > 0) this.startCountdown();
                },

                startCountdown() {
                    if (this.interval) clearInterval(this.interval);

                    this.interval = setInterval(() => {
                        this.remainingTime--;
                        if (this.remainingTime <= 0) {
                            clearInterval(this.interval);
                            this.endChargingSession();
                        }
                    }, 1000);
                },

                pauseCountdown(remainingTime) {
                    if (this.interval) clearInterval(this.interval);

                    this.remainingTime = remainingTime;
                    this.isCharging = false;
                    this.isPaused = true;

                    document.getElementById(`port${port}`).classList.add('paused', 'pulse-animation');
                    this.showToast(`Charging paused. ${this.formatTime(remainingTime)} remaining`, 'warning');
                },

                resumeCountdown(remainingTime) {
                    this.remainingTime = remainingTime;
                    this.isCharging = true;
                    this.isPaused = false;

                    document.getElementById(`port${port}`).classList.remove('paused', 'pulse-animation');

                    this.startCountdown();
                    this.showToast(`Charging resumed with ${this.formatTime(this.remainingTime)} remaining`, 'success');
                },

                displayPausedTimer() {
                    this.showToast(`Paused - ${this.formatTime(this.remainingTime)} remaining`, 'warning');
                    document.getElementById(`port${port}`).classList.add('paused', 'pulse-animation');
                },

                addToToken(number) {
                    if (this.token.length < 5) this.token += number;
                },

                clearToken() {
                    this.token = '';
                },

                handleFormSubmit() {
                    fetch('{{ route("customer.validate") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({ token: this.token, port: port })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.isValidated = true;
                            this.duration = data.duration;
                            this.showToast('Token validated successfully', 'success');
                        } else {
                            this.showToast(data.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        this.showToast('An error occurred. Please try again.', 'error');
                    });
                },

                handleStartCharging() {
                    if (this.isCharging || this.isStarting) return;

                    this.isStarting = true;

                    fetch('{{ route("start-charging") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({ token: this.token, port })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.isCharging = true;
                            this.isPaused = false;
                            this.isStarting = false;
                            this.remainingTime = this.duration * 60;
                            this.startCountdown();
                            this.showToast('Charging started successfully', 'success');
                        } else {
                            this.isStarting = false;
                            this.showToast(data.message, 'error');
                        }
                    })
                    .catch(error => {
                        this.isStarting = false;
                        console.error('Error:', error);
                        this.showToast('An error occurred while starting the session.', 'error');
                    });
                },

                endChargingSession() {
                    fetch(`/customer/${port}/end`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({ token: this.token })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.showToast('Charging session ended successfully', 'success');
                            setTimeout(() => this.resetState(), 3000);
                        } else {
                            this.showToast('Failed to notify the server.', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        this.showToast('Error ending the session.', 'error');
                    });
                },

                resetState() {
                    this.token = '';
                    this.isValidated = false;
                    this.isCharging = false;
                    this.isPaused = false;
                    this.remainingTime = 0;
                },

                formatTime(seconds) {
                    const minutes = Math.floor(seconds / 60);
                    const remainingSeconds = seconds % 60;
                    return `${minutes}:${remainingSeconds < 10 ? '0' : ''}${remainingSeconds}`;
                },

                showToast(message, type) {
                    const toastContainer = document.getElementById('toast-container');

                    // Check if a toast with the same message exists
                    const existingToast = Array.from(toastContainer.children).find(toast => toast.innerText === message);
                    if (existingToast) return
                    
                    const toast = document.createElement('div');
                    toast.className = `flex items-center w-full max-w-xs p-4 mb-4 text-gray-500 bg-white rounded-lg shadow ${type === 'success' ? 'text-green-500' : 'text-red-500'}`;
                    toast.innerHTML = `
                        <div class="inline-flex items-center justify-center w-8 h-8 ${type === 'success' ? 'bg-green-100' : 'bg-red-100'} rounded-lg">
                            ${type === 'success' 
                                ? '<svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20"><path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 8.207-4 4a1 1 0 0 1-1.414 0l-2-2a1 1 0 0 1 1.414-1.414L9 10.586l3.293-3.293a1 1 0 0 1 1.414 1.414Z"/></svg>'
                                : '<svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20"><path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 11.793a1 1 0 1 1-1.414 1.414L10 11.414l-2.293 2.293a1 1 0 0 1-1.414-1.414L8.586 10 6.293 7.707a1 1 0 0 1 1.414-1.414L10 8.586l2.293-2.293a1 1 0 0 1 1.414 1.414L11.414 10l2.293  2.293Z"/></svg>'
                            }
                        </div>
                        <div class="ml-3 text-sm font-normal">${message}</div>
                    `;
                    document.getElementById('toast-container').appendChild(toast);
                    setTimeout(() => toast.remove(), 5000);
                }
            }
        }
    </script>        
</body>
</html>
