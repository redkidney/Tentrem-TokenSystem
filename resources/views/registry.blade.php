<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registry - Generate Token</title>
    <!-- Include Tailwind CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://unpkg.com/alpinejs@2.8.2/dist/alpine.js" defer></script>
</head>
<body class="bg-gray-100 p-6">

    <!-- Profile Icon with Dropdown -->
    <div class="relative" x-data="{ open: false }">
        <div class="absolute top-4 right-4">
            <button @click="open = !open" class="w-10 h-10 bg-gray-600 text-white rounded-full flex items-center justify-center">
                {{ Auth::user()->name[0] }}
            </button>
            <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-2 z-50">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="block w-full text-left px-4 py-2 text-gray-700 hover:bg-gray-100">Logout</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Page Content -->
    <div class="max-w-md mx-auto mt-12">
        <h1 class="text-center text-3xl font-bold mb-6">Generate Charging Token</h1>

        <!-- Success message -->
        <div x-data="{ successMessage: @if(session('success')) '{{ session('success') }}' @else '' @endif }">
            <p x-show="successMessage" x-text="successMessage" class="text-center text-green-600 font-semibold mb-6"></p>
        </div>

        <!-- Token Generation Form -->
        <div class="bg-white p-6 rounded-lg shadow-md" x-data="{ formData: { guest_name: '', room_no: '', phone: '', expiry: 720, duration: 60 } }">
            <form method="POST" action="{{ route('generate-token') }}">
                @csrf

                <!-- Guest Name Input -->
                <div class="mb-4">
                    <label for="guest_name" class="block text-gray-700">Guest Name:</label>
                    <input type="text" name="guest_name" id="guest_name" x-model="formData.guest_name" required class="w-full p-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Enter guest name">
                </div>

                <!-- Room Number Input -->
                <div class="mb-4">
                    <label for="room_no" class="block text-gray-700">Room Number:</label>
                    <input type="text" name="room_no" id="room_no" x-model="formData.room_no" required class="w-full p-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Enter room number">
                </div>

                <!-- Phone Number Input -->
                <div class="mb-4">
                    <label for="phone" class="block text-gray-700">Phone Number:</label>
                    <input type="text" name="phone" id="phone" x-model="formData.phone" required class="w-full p-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Enter phone number">
                </div>

                <!-- Token Expiry Input -->
                <div class="mb-4">
                    <label for="expiry" class="block text-gray-700">Token Expiry (in minutes):</label>
                    <input type="number" name="expiry" id="expiry" x-model="formData.expiry" required class="w-full p-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>

                <!-- Timer Duration Input -->
                <div class="mb-4">
                    <label for="duration" class="block text-gray-700">Timer Duration (in minutes):</label>
                    <input type="number" name="duration" id="duration" x-model="formData.duration" required class="w-full p-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>

                <!-- Submit Button -->
                <button type="submit" class="w-full bg-green-500 text-white py-2 rounded-md hover:bg-green-600 transition duration-300">Generate Token</button>
            </form>
        </div>
    </div>

    <!-- Generated Tokens Table -->
    <div class="max-w-4xl mx-auto mt-12">
        <h2 class="text-center text-2xl font-bold mb-6">Generated Tokens</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white rounded-lg shadow-md">
                <thead>
                    <tr>
                        <th class="px-4 py-2 bg-gray-100 text-gray-700 text-center">Token</th>
                        <th class="px-4 py-2 bg-gray-100 text-gray-700 text-center">Expiry</th>
                        <th class="px-4 py-2 bg-gray-100 text-gray-700 text-center">Duration</th>
                        <th class="px-4 py-2 bg-gray-100 text-gray-700 text-center">Used</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tokens as $token)
                        <tr class="border-b">
                            <td class="px-4 py-2 text-center">{{ $token->token }}</td>
                            <td class="px-4 py-2 text-center">{{ $token->expiry->format('Y-m-d H:i') }}</td>
                            <td class="px-4 py-2 text-center">{{ $token->duration }} min</td>
                            <td class="px-4 py-2 text-center">{{ $token->used ? 'Yes' : 'No' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if($tokens->isEmpty())
                <p class="text-center mt-4 text-gray-500">No tokens have been generated yet.</p>
            @endif
        </div>
    </div>

    <!-- Include Alpine.js for Dropdown -->
    <script src="https://unpkg.com/alpinejs@2.8.2/dist/alpine.js" defer></script>
</body>
</html>
