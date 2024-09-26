<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer - Charging Port 1</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            padding: 20px;
        }
        h1 {
            text-align: center;
        }
        #token-form, #countdown, #instructions, #start-btn {
            margin: 20px auto;
            width: 300px;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        button {
            display: inline-block;
            width: 100%;
            padding: 10px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }
        #timer {
            font-size: 2rem;
            text-align: center;
        }
        #instructions {
            display: none;
            text-align: center;
            font-size: 1.2rem;
        }
        #start-btn {
            display: none;
        }
        #countdown {
            display: none;
            text-align: center;
        }
        #error-message {
            color: red;
            text-align: center;
        }
    </style>
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
                    document.getElementById('instructions').style.display = 'block';
                    document.getElementById('start-btn').style.display = 'block';
                    document.getElementById('token-form').style.display = 'none';
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
                    document.getElementById('start-btn').style.display = 'none';
                    document.getElementById('countdown').style.display = 'block';
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
</head>
<body>
    <h1>Enter Charging Token for Port 1</h1>

    <!-- Token Form -->
    <form id="token-form" onsubmit="handleFormSubmit(event)">
        <label for="token">Enter Token:</label>
        <input type="text" id="token" name="token" placeholder="6-character token" required>
        <button type="submit">Submit Token</button>
    </form>

    <div id="error-message"></div>

    <!-- Instructions -->
    <div id="instructions">
        <p>Please plug in the charger for Port 1 and press start.</p>
    </div>

    <!-- Start Button -->
    <div id="start-btn">
        <button onclick="handleStartCharging()">Start Charging</button>
    </div>

    <!-- Hidden input to store the duration of charging -->
    <input type="hidden" id="duration" value="">

    <!-- Countdown Timer -->
    <div id="countdown">
        <h2>Countdown Timer</h2>
        <p id="timer">--:--</p>
    </div>
</body>
</html>
