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
        .shake {
            animation: shake 0.3s;
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col justify-center p-4">

    <!-- Flowbite Toast Container -->
    <div id="toast-container" class="fixed top-5 right-5 z-50"></div>

    <!-- Split Screen Layout for Two Charging Ports -->
    <div class="flex flex-col md:flex-row space-y-4 md:space-y-0 md:space-x-4">

        @foreach([1, 2] as $port)
        <div id="port{{ $port }}" x-data="chargingStation({{ $port }}, '{{ $ports[$port-1]->status }}', '{{ $ports[$port-1]->start_time }}', '{{ $ports[$port-1]->end_time }}', {{ $ports[$port-1]->duration }})" x-init="init()" class="w-full md:w-1/2 bg-white shadow-lg rounded-lg p-6">
            <h1 class="text-2xl font-bold text-center mb-4" :class="{'bg-gradient-to-r from-blue-500 to-green-500 text-white p-2 rounded': isCharging}">Charging Port {{ $port }}</h1>
            <form @submit.prevent="handleFormSubmit" x-show="!isValidated && !isCharging" class="space-y-4">
                <label :for="'token'+{{ $port }}" class="block text-sm font-medium text-gray-700">Enter Token:</label>
                <input :id="'token'+{{ $port }}" type="text" x-model="token" placeholder="5-character token" required maxlength="5"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" readonly>
                <p class="text-sm text-gray-500">Characters remaining: <span x-text="5 - token.length"></span></p>
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
            <div x-show="isValidated && !isCharging" class="text-center space-y-4">
                <p class="text-gray-700">Please plug in the charger for Port {{ $port }} and press start.</p>
                <button @click="handleStartCharging" class="w-full bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600 transition duration-300">Start Charging</button>
            </div>
            <div x-show="isCharging" class="mt-4 p-3 bg-green-100 border border-green-400 text-green-700 rounded">Charging in progress</div>

            <!-- Countdown Timer -->
            <div x-show="isCharging" class="text-center space-y-4 mt-4">
                <div class="relative w-48 h-48 mx-auto">
                    <svg class="w-full h-full" viewBox="0 0 100 100">
                        <circle class="text-gray-200 stroke-current" stroke-width="8" cx="50" cy="50" r="40" fill="transparent" />
                        <circle class="text-green-500 stroke-current" stroke-width="8" stroke-linecap="round"
                                cx="50" cy="50" r="40" fill="transparent"
                                :stroke-dasharray="2 * Math.PI * 40"
                                :stroke-dashoffset="2 * Math.PI * 40 * (1 - remainingTime / (duration * 60))" />
                    </svg>
                    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 text-center">
                        <p class="text-4xl font-bold" x-text="formatTime(remainingTime)"></p>
                        <p class="text-sm text-gray-500">Remaining</p>
                    </div>
                </div>
                <p class="text-lg font-semibold text-green-600">Charging in progress</p>
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
                remainingTime: 0,
                duration: duration,
                interval: null,
                isStarting: false,

                init() {
                    if (this.isCharging) {
                        this.isValidated = true;
                        this.calculateRemainingTime(startTime, endTime);
                    }

                    window.Echo.channel('charging-port')
                        .listen('ChargingStatus', (e) => {
                            if (e.status === 'charging_started' && e.port === port) {
                                if (!this.isCharging) {
                                    this.handleStartCharging();
                                }
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

                    if (this.remainingTime > 0) {
                        this.startCountdown();
                    }
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

                addToToken(number) {
                    if (this.token.length < 5) {
                        this.token += number;
                    }
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
                            setTimeout(() => {
                                this.resetState();
                            }, 3000);
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
                    this.remainingTime = 0;
                },

                formatTime(seconds) {
                    const minutes = Math.floor(seconds / 60);
                    const remainingSeconds = seconds % 60;
                    return `${minutes}:${remainingSeconds < 10 ? '0' : ''}${remainingSeconds}`;
                },

                showToast(message, type) {
                    const toast = document.createElement('div');
                    toast.setAttribute('id', Date.now());
                    toast.className = `flex items-center w-full max-w-xs p-4 mb-4 text-gray-500 bg-white rounded-lg shadow dark:text-gray-400 dark:bg-gray-800 ${type === 'success' ? 'text-green-500' : 'text-red-500'}`;
                    toast.setAttribute('role', 'alert');
                    
                    toast.innerHTML = `
                        <div class="inline-flex items-center justify-center flex-shrink-0 w-8 h-8 ${type === 'success' ? 'text-green-500 bg-green-100' : 'text-red-500 bg-red-100'} rounded-lg dark:bg-green-800 dark:text-green-200">
                            ${type === 'success' 
                                ? '<svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20"><path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 8.207-4 4a1 1 0 0 1-1.414 0l-2-2a1 1 0 0 1 1.414-1.414L9 10.586l3.293-3.293a1 1 0 0 1 1.414 1.414Z"/></svg>'
                                : '<svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20"><path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 11.793a1 1 0 1 1-1.414 1.414L10 11.414l-2.293 2.293a1 1 0 0 1-1.414-1.414L8.586 10 6.293 7.707a1 1 0 0 1 1.414-1.414L10 8.586l2.293-2.293a1 1 0 0 1 1.414 1.414L11.414 10l2.293  2.293Z"/></svg>'
                            }
                            <span class="sr-only">${type === 'success' ? 'Success' : 'Error'} icon</span>
                        </div>
                        <div class="ml-3 text-sm font-normal">${message}</div>
                        <button type="button" class="ml-auto -mx-1.5 -my-1.5 bg-white text-gray-400 hover:text-gray-900 rounded-lg focus:ring-2 focus:ring-gray-300 p-1.5 hover:bg-gray-100 inline-flex items-center justify-center h-8 w-8 dark:text-gray-500 dark:hover:text-white dark:bg-gray-800 dark:hover:bg-gray-700" data-dismiss-target="#${toast.id}" aria-label="Close">
                            <span class="sr-only">Close</span>
                            <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                            </svg>
                        </button>
                    `;

                    document.getElementById('toast-container').appendChild(toast);

                    setTimeout(() => {
                        toast.remove();
                    }, 5000);
                }
            }
        }
    </script>

</body>
</html>