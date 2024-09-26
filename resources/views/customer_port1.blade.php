<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Customer - Charging Port 1</title>
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <script>
            function handleFormSubmit(event) 
            {
                event.preventDefault();

                const token = document.getElementById('token').value;
                const port = 1;
                const errorMessage = document.getElementById('error-message');

                errorMessage.textContent = '';

                fetch('{{ route("customer.validate") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ token, port })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('instructions').classList.remove('hidden');
                        document.getElementById('start-btn').classList.remove('hidden');
                        document.getElementById('token-form').classList.add('hidden');
                        document.getElementById('duration').value = data.duration;
                    } else {
                        errorMessage.textContent = data.message;
                    }
                })
                .catch(error => {
                    errorMessage.textContent = 'An error occurred. Please try again.';
                    console.error('Error:', error);
                });
            }

            function handleStartCharging() 
            {
                const token = document.getElementById('token').value;
                const port = 1;
                const duration = document.getElementById('duration').value;

                fetch('{{ route("start-charging") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ token, port })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        startCountdown(duration);
                        document.getElementById('start-btn').classList.add('hidden');
                        document.getElementById('countdown').classList.remove('hidden');
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            }

            function startCountdown(duration) 
            {
                const timerDisplay = document.getElementById('timer');
                let endTime = Date.now() + duration * 60000; // Convert minutes to milliseconds

                function updateTimer() {
                    const remainingTime = Math.max(0, endTime - Date.now());
                    const minutes = Math.floor(remainingTime / 60000);
                    const seconds = Math.floor((remainingTime % 60000) / 1000);

                    timerDisplay.textContent = `${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;

                    if (remainingTime <= 0) {
                        clearInterval(interval);
                        endChargingSession();
                    }
                }

                updateTimer();
                const interval = setInterval(updateTimer, 1000);
            }

            function endChargingSession() 
            {
                const port = 1;
                const token = document.getElementById('token').value; // Get the token from input

                // Disable the function from being called multiple times
                if (this.endChargingCalled) {
                    return; // Prevent duplicate stop commands
                }

                this.endChargingCalled = true; // Mark that the stop has been requested

                // Send token and port in the request
                fetch(`/customer/${port}/end`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ token }) // Include the token in the body
                })
                .then(response => response.json())
                .then(data => {
                    console.log(data);
                    if (data.success) {
                        alert("Charging session has ended.");
                        setTimeout(() => {
                            window.location.href = `/customer/${port}`;
                        }, 2000);
                    } else {
                        alert("Failed to notify the server.");
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            }
        </script>
        <link href="{{ asset('css/theme.css') }}" rel="stylesheet" type="text/css">
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-gray-100 min-h-screen flex flex-col items-center justify-center">

        <h1 class="text-2xl font-bold mb-6">Enter Charging Token for Port 1</h1>

        <!-- Token Form -->
        <form id="token-form" onsubmit="handleFormSubmit(event)" class="bg-white p-6 rounded-lg shadow-lg w-80 mb-4">
            <label for="token" class="block text-sm font-medium text-gray-700 mb-2">Enter Token:</label>
            <input type="text" id="token" name="token" placeholder="6-character token" required class="block w-full border border-gray-300 rounded-md p-2 mb-4">
            <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded-md w-full hover:bg-green-600">Submit Token</button>
        </form>

        <div id="error-message" class="text-red-500 mb-4 text-center"></div>

        <!-- Instructions -->
        <div id="instructions" class="hidden text-center text-lg mb-4">
            <p class="text-gray-700">Please plug in the charger for Port 1 and press start.</p>
        </div>

        <!-- Start Button -->
        <div id="start-btn" class="hidden mb-4">
            <button onclick="handleStartCharging()" class="bg-green-500 text-white px-4 py-2 rounded-md w-full hover:bg-green-600">Start Charging</button>
        </div>

        <!-- Hidden input to store the duration of charging -->
        <input type="hidden" id="duration" value="">

        <!-- Countdown Timer -->
        <div id="countdown" class="hidden text-center">
            <h2 class="text-xl font-semibold mb-2">Countdown Timer</h2>
            <p id="timer" class="text-2xl font-bold text-gray-800">--:--</p>
        </div>
    </body>
</html>
