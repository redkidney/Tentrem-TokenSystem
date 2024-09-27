<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer - Charging Port 1</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col items-center justify-center p-4">
    <div x-data="chargingStation()" class="w-full max-w-md">
        <div class="bg-white shadow-lg rounded-lg overflow-hidden">
            <div class="p-6">
                <h1 class="text-2xl font-bold text-center mb-2">Charging Port 1</h1>
                <p x-show="!isValidated" class="text-center text-gray-600 mb-6">Enter your charging token to begin</p>
                <p x-show="isValidated && !isCharging" class="text-center text-gray-600 mb-6">Ready to start charging</p>
                <p x-show="isCharging" class="text-center text-gray-600 mb-6">Charging session in progress</p>

                <!-- Token Form -->
                <form id="token-form" x-show="!isValidated" @submit.prevent="handleFormSubmit" class="space-y-4">
                    <div>
                        <label for="token" class="block text-sm font-medium text-gray-700 mb-2">Enter Token:</label>
                        <input type="text" id="token" x-model="token" placeholder="6-character token" required maxlength="6"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <button type="submit" class="w-full bg-green-500 text-white px-4 py-2 rounded-md hover:bg-green-600 transition duration-300">
                        Submit Token
                    </button>
                </form>

                <!-- Instructions -->
                <div id="instructions" x-show="isValidated && !isCharging" class="text-center space-y-4">
                    <p class="text-gray-700">Please plug in the charger for Port 1 and press start.</p>
                    <button id="start-btn" @click="handleStartCharging" class="w-full bg-green-500 text-white px-4 py-2 rounded-md hover:bg-green-600 transition duration-300">
                        Start Charging
                    </button>
                </div>

                <!-- Countdown Timer -->
                <div id="countdown" x-show="isCharging" class="text-center space-y-4">
                    <div class="relative w-48 h-48 mx-auto">
                        <svg class="w-full h-full" viewBox="0 0 100 100">
                            <circle class="text-gray-200 stroke-current" stroke-width="8" cx="50" cy="50" r="40" fill="transparent" />
                            <circle class="text-green-500 stroke-current" stroke-width="8" stroke-linecap="round"
                                    cx="50" cy="50" r="40" fill="transparent"
                                    :stroke-dasharray="2 * Math.PI * 40"
                                    :stroke-dashoffset="2 * Math.PI * 40 * (1 - remainingTime / (duration * 60))" />
                        </svg>
                        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 text-center">
                            <p id="timer" class="text-4xl font-bold" x-text="formatTime(remainingTime)"></p>
                            <p class="text-sm text-gray-500">Remaining</p>
                        </div>
                    </div>
                    <p class="text-lg font-semibold text-green-600">Charging in progress</p>
                </div>

                <!-- Error Message -->
                <div id="error-message" x-show="errorMessage" x-text="errorMessage" class="mt-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded"></div>

                <!-- Temporary Notification -->
                <div x-show="showNotification" 
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 transform scale-90"
                     x-transition:enter-end="opacity-100 transform scale-100"
                     x-transition:leave="transition ease-in duration-300"
                     x-transition:leave-start="opacity-100 transform scale-100"
                     x-transition:leave-end="opacity-0 transform scale-90"
                     class="fixed top-4 right-4 bg-green-500 text-white px-4 py-2 rounded-md shadow-lg">
                    Charging session has ended
                </div>
            </div>
        </div>
    </div>

    <script>
        function chargingStation() {
            return {
                token: '',
                isValidated: false,
                isCharging: false,
                duration: 0,
                remainingTime: 0,
                errorMessage: '',
                showNotification: false,
                handleFormSubmit(event) {
                    event.preventDefault();
                    this.errorMessage = '';
                    const port = 1;

                    fetch('{{ route("customer.validate") }}', {
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
                            this.isValidated = true;
                            this.duration = data.duration;
                        } else {
                            this.errorMessage = data.message;
                        }
                    })
                    .catch(error => {
                        this.errorMessage = 'An error occurred. Please try again.';
                        console.error('Error:', error);
                    });
                },
                handleStartCharging() {
                    const port = 1;
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
                            this.remainingTime = this.duration * 60;
                            this.startCountdown();
                        } else {
                            this.errorMessage = data.message;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        this.errorMessage = 'An error occurred while starting the charging session.';
                    });
                },
                startCountdown() {
                    const interval = setInterval(() => {
                        this.remainingTime--;
                        if (this.remainingTime <= 0) {
                            clearInterval(interval);
                            this.endChargingSession();
                        }
                    }, 1000);
                },
                endChargingSession() {
                    const port = 1;
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
                            this.showNotification = true;
                            setTimeout(() => {
                                this.showNotification = false;
                                window.location.href = `/customer/${port}`;
                            }, 3000);
                        } else {
                            this.errorMessage = "Failed to notify the server.";
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        this.errorMessage = 'An error occurred while ending the charging session.';
                    });
                },
                formatTime(seconds) {
                    const minutes = Math.floor(seconds / 60);
                    const remainingSeconds = seconds % 60;
                    return `${minutes}:${remainingSeconds < 10 ? '0' : ''}${remainingSeconds}`;
                }
            }
        }
    </script>
</body>
</html>