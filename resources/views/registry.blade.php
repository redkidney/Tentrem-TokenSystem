<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registry - Generate Token</title>
    @vite('resources/css/app.css')
    @vite('resources/js/app.js')
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@2.8.2/dist/alpine.js" defer></script>
</head>

<body class="bg-gray-100 p-6">

    <!-- Profile Icon with Dropdown -->
    <div class="relative" x-data="{ open: false }">
        <div class="absolute top-0 right-0">
            <button @click="open = !open" class="w-10 h-10 bg-gray-600 text-white rounded-full flex items-center justify-center">
                {{ Auth::user()->name[0] }}
            </button>
            <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-2 z-50">
                <form method="GET" action="{{ route('vouchers.create') }}">
                    @csrf
                    <button type="submit" class="block w-full text-left px-4 py-2 text-gray-700 hover:bg-blue-100">Create Voucher</button>
                </form>
                <form method="GET" action="{{ route('reports.charging-sessions') }}">
                    @csrf
                    <button type="submit" class="block w-full text-left px-4 py-2 text-gray-700 hover:bg-blue-100">Reports</button>
                </form>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="block w-full text-left px-4 py-2 text-gray-700 hover:bg-red-100">Logout</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Page Content -->
    <div class="max-w-6xl mx-auto mt-12">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <!-- Token Generation Form (Left Side) -->
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h1 class="text-center text-3xl font-bold mb-6">Generate Charging Token</h1>

                <!-- Success Message -->
                @if (session('success'))
                    <p class="text-center text-green-600 font-semibold mb-6">{{ session('success') }}</p>
                @endif

                <!-- Token Generation Form -->
                <form method="POST" action="{{ route('generate-token') }}">
                    @csrf
                    <div class="mb-4">
                        <label for="guest_name" class="block text-gray-700">Guest Name:</label>
                        <input type="text" name="guest_name" required
                               class="w-full p-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                               placeholder="Enter guest name">
                    </div>

                    <div class="mb-4">
                        <label for="room_no" class="block text-gray-700">Room Number:</label>
                        <input type="text" name="room_no" required
                               class="w-full p-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                               placeholder="Enter room number">
                    </div>

                    <div class="mb-4">
                        <label for="phone" class="block text-gray-700">Phone Number:</label>
                        <input type="text" name="phone"
                               class="w-full p-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                               placeholder="Enter phone number">
                    </div>

                    <div class="mb-4">
                        <label for="voucher_id" class="block text-gray-700">Voucher:</label>
                        <select name="voucher_id" required
                                class="w-full p-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                            <option value="" disabled selected>Select a voucher</option>
                            @foreach($vouchers as $voucher)
                                <option value="{{ $voucher->id }}">
                                    {{ $voucher->voucher_name }} - {{ $voucher->duration }} min
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit"
                            class="w-full bg-green-500 text-white py-2 rounded-md hover:bg-green-600 active:bg-green-700 disabled:bg-gray-300 transition duration-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        Generate Token
                    </button>
                </form>
            </div>

            <!-- Generated Tokens Section (Right Side) -->
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-center text-2xl font-bold mb-6">Generated Tokens</h2>
                <table class="min-w-full bg-white border border-gray-300 rounded-lg shadow-md">
                    <thead>
                        <tr class="bg-gray-100 text-gray-700">
                            <th class="px-4 py-2 text-center">Token</th>
                            <th class="px-4 py-2 text-center">Expiry</th>
                            <th class="px-4 py-2 text-center">Duration</th>
                            <th class="px-4 py-2 text-center">Used</th>
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
            </div>
        </div>
    </div>

    <!-- Client-side Printing Script -->
    @if(session('tokenData'))
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                const tokenData = @json(session('tokenData'));

                const printWindow = window.open('', '', 'width=300,height=400');
                printWindow.document.write('<html><head><title>Print Token</title>');
                printWindow.document.write('<style>');
                printWindow.document.write(`
                    body {
                        font-family: monospace;
                        text-align: center;
                        margin: 0;
                        padding: 0;
                    }
                    .header, .footer {
                        font-weight: bold;
                        margin-bottom: 10px;
                    }
                    .divider {
                        border-top: 1px dashed #000;
                        margin: 10px 0;
                    }
                    .token {
                        font-size: 24px;
                        font-weight: bold;
                        margin: 20px 0;
                    }
                    .details {
                        font-size: 14px;
                        line-height: 1.5;
                    }
                `);
                printWindow.document.write('</style></head><body>');

                // Header
                printWindow.document.write('<div class="header">Hotel Tentrem Yogyakarta</div>');
                printWindow.document.write('<div class="divider"></div>');

                // Guest and Token Details
                printWindow.document.write('<div class="details">');
                printWindow.document.write(`<p>Guest: ${tokenData.guest_name}</p>`);
                printWindow.document.write(`<p>Room No: ${tokenData.room_no}</p>`);
                printWindow.document.write('</div>');

                // Token
                printWindow.document.write('<div class="token">');
                printWindow.document.write(`TOKEN: ${tokenData.token}`);
                printWindow.document.write('</div>');

                // Expiry and Duration
                printWindow.document.write('<div class="details">');
                printWindow.document.write(`<p>Expiry: ${tokenData.expiry}</p>`);
                printWindow.document.write(`<p>Duration: ${tokenData.duration} minutes</p>`);
                printWindow.document.write('</div>');

                // Footer
                printWindow.document.write('<div class="divider"></div>');
                printWindow.document.write('<div class="footer">Thank You!</div>');

                printWindow.document.write('</body></html>');
                printWindow.document.close();
                printWindow.print();
                printWindow.close();
            });
        </script>
    @endif
</body>
</html>