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

    <!-- Split Screen Layout for Two Charging Ports -->
    <div class="flex flex-col md:flex-row space-y-4 md:space-y-0 md:space-x-4">

        <!-- Charging Port 1 -->
        <div id="port1" x-data="chargingStation(1)" class="w-full md:w-1/2 bg-white shadow-lg rounded-lg p-6">
            <h1 class="text-2xl font-bold text-center mb-4">Charging Port 1</h1>
            <form @submit.prevent="handleFormSubmit" x-show="!isValidated" class="space-y-4">
                <label for="token1" class="block text-sm font-medium text-gray-700">Enter Token:</label>
                <input type="text" id="token1" x-model="token" placeholder="5-character token" required maxlength="5"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" readonly>
                <p class="text-sm text-gray-500">Characters remaining: <span x-text="5 - token.length"></span></p>
                <div class="grid grid-cols-3 gap-4">
                    <!-- Number Buttons -->
                    <template x-for="number in ['1', '2', '3', '4', '5', '6', '7', '8', '9']" :key="number">
                        <button type="button" @click="addToToken(number)" 
                                class="bg-gray-200 py-2 rounded-md text-center hover:bg-gray-400 active:bg-gray-500 transition duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <span x-text="number"></span>
                        </button>
                    </template>
                    <!-- Clear, Zero, Submit -->
                    <button type="button" @click="clearToken()" 
                            class="bg-red-200 py-2 rounded-md text-center col-span-1 hover:bg-red-300 active:bg-red-400 transition duration-200 focus:outline-none focus:ring-2 focus:ring-red-500">
                        Clear
                    </button>
                    <button type="button" @click="addToToken('0')" 
                            class="bg-gray-200 py-2 rounded-md text-center col-span-1 hover:bg-gray-400 active:bg-gray-500 transition duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        0
                    </button>
                    <button type="submit" :disabled="token.length < 5" 
                            class="bg-green-500 text-white py-2 rounded-md col-span-1 disabled:bg-gray-300 hover:bg-green-600 active:bg-green-700 transition duration-300 focus:outline-none focus:ring-2 focus:ring-green-500">
                        Submit
                    </button>
                </div>
            </form>
            <div x-show="isValidated && !isCharging" class="text-center space-y-4">
                <p class="text-gray-700">Please plug in the charger for Port 1 and press start.</p>
                <button @click="handleStartCharging" class="w-full bg-green-500 text-white px-4 py-2 rounded-md hover:bg-green-600 transition duration-300">Start Charging</button>
            </div>
            <div x-show="isCharging" class="mt-4 p-3 bg-green-100 border border-green-400 text-green-700 rounded">Charging in progress</div>

            <!-- Countdown Timer for Port 1 -->
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
            <div x-show="showError" class="mt-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded" x-text="errorMessage"></div>
            <div x-show="showNotification" class="mt-4 p-3 bg-green-100 border border-green-400 text-green-700 rounded">
                Charging session ended successfully. The page will refresh shortly.
            </div>
        </div>

        <!-- Charging Port 2 -->
        <div id="port2" x-data="chargingStation(2)" class="w-full md:w-1/2 bg-white shadow-lg rounded-lg p-6">
            <h1 class="text-2xl font-bold text-center mb-4">Charging Port 2</h1>
            <form @submit.prevent="handleFormSubmit" x-show="!isValidated" class="space-y-4">
                <label for="token2" class="block text-sm font-medium text-gray-700">Enter Token:</label>
                <input type="text" id="token2" x-model="token" placeholder="5-character token" required maxlength="5"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" readonly>
                <p class="text-sm text-gray-500">Characters remaining: <span x-text="5 - token.length"></span></p>
                <div class="grid grid-cols-3 gap-4">
                    <!-- Number Buttons -->
                    <template x-for="number in ['1', '2', '3', '4', '5', '6', '7', '8', '9']" :key="number">
                        <button type="button" @click="addToToken(number)" 
                                class="bg-gray-200 py-2 rounded-md text-center hover:bg-gray-400 active:bg-gray-500 transition duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <span x-text="number"></span>
                        </button>
                    </template>
                    <!-- Clear, Zero, Submit -->
                    <button type="button" @click="clearToken()" 
                            class="bg-red-200 py-2 rounded-md text-center col-span-1 hover:bg-red-300 active:bg-red-400 transition duration-200 focus:outline-none focus:ring-2 focus:ring-red-500">
                        Clear
                    </button>
                    <button type="button" @click="addToToken('0')" 
                            class="bg-gray-200 py-2 rounded-md text-center col-span-1 hover:bg-gray-400 active:bg-gray-500 transition duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        0
                    </button>
                    <button type="submit" :disabled="token.length < 5" 
                            class="bg-green-500 text-white py-2 rounded-md col-span-1 disabled:bg-gray-300 hover:bg-green-600 active:bg-green-700 transition duration-300 focus:outline-none focus:ring-2 focus:ring-green-500">
                        Submit
                    </button>
                </div>
            </form>
            <div x-show="isValidated && !isCharging" class="text-center space-y-4">
                <p class="text-gray-700">Please plug in the charger for Port 2 and press start.</p>
                <button @click="handleStartCharging" class="w-full bg-green-500 text-white px-4 py-2 rounded-md hover:bg-green-600 transition duration-300">Start Charging</button>
            </div>
            <div x-show="isCharging" class="mt-4 p-3 bg-green-100 border border-green-400 text-green-700 rounded">Charging in progress</div>

            <!-- Countdown Timer for Port 2 -->
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
            <div x-show="showError" class="mt-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded" x-text="errorMessage"></div>
            <div x-show="showNotification" class="mt-4 p-3 bg-green-100 border border-green-400 text-green-700 rounded">
                Charging session ended successfully. The page will refresh shortly.
            </div>
        </div>

    </div>

    <script>
        function chargingStation(port) {
            return {
                token: '',
                isValidated: false,
                isCharging: false,
                duration: 0,
                remainingTime: 0,
                errorMessage: '',
                showNotification: false,
                showError: false,
    
                addToToken(number) {
                    if (this.token.length < 5) {
                        this.token += number;
                        this.showError = false;
                    }
                },
                clearToken() {
                    this.token = '';
                },
                handleFormSubmit(event) {
                    event.preventDefault();
                    this.errorMessage = '';
                    this.showError = false;
    
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
                            this.showError = true;
                        }
                    })
                    .catch(error => {
                        this.errorMessage = 'An error occurred. Please try again.';
                        this.showError = true;
                        console.error('Error:', error);
                    });
                },
                handleStartCharging() {
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
                            this.showError = true;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        this.errorMessage = 'An error occurred while starting the charging session.';
                        this.showError = true;
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
                                this.refreshPortContent();
                            }, 3000);
                        } else {
                            this.errorMessage = "Failed to notify the server.";
                            this.showError = true;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        this.errorMessage = 'An error occurred while ending the charging session.';
                        this.showError = true;
                    });
                },
                refreshPortContent() {
                    fetch(`{{ route('customer.port-status', ['port' => ':port']) }}`.replace(':port', port))
                        .then(response => response.json())
                        .then(data => {
                            // Update the component state with the new data
                            Object.assign(this, data);
                        })
                        .catch(error => {
                            console.error('Error refreshing port content:', error);
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