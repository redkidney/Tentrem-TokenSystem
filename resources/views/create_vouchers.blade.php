<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Vouchers</title>
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
                <form method="GET" action="{{ route('registry') }}">
                    <button type="submit" class="block w-full text-left px-4 py-2 text-gray-700 hover:bg-blue-100">Generate Token</button>
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

    <!-- Page Content with Grid Layout -->
    <div class="max-w-6xl mx-auto mt-12">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            
            <!-- Voucher Creation Form -->
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h1 class="text-center text-3xl font-bold mb-6">Create Voucher</h1>

                <!-- Success Message -->
                @if (session('success'))
                    <p class="text-center text-green-600 font-semibold mb-6">{{ session('success') }}</p>
                @endif

                <!-- Voucher Form -->
                <form method="POST" action="{{ route('vouchers.store') }}">
                    @csrf

                    <div class="mb-4">
                        <label for="voucher_name" class="block text-gray-700">Voucher Name:</label>
                        <input type="text" name="voucher_name" required class="w-full p-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Enter voucher name">
                    </div>

                    <div class="mb-4">
                        <label for="duration" class="block text-gray-700">Duration (in minutes):</label>
                        <input type="number" name="duration" value="60" required class="w-full p-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Enter duration">
                    </div>

                    <div class="mb-4">
                        <label for="price" class="block text-gray-700">Price (in IDR):</label>
                        <input type="text" name="price" value="0" required class="w-full p-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Enter price">
                    </div>

                    <button type="submit" class="w-full bg-green-500 text-white py-2 rounded-md hover:bg-green-600 active:bg-green-700 transition duration-300 focus:outline-none focus:ring-2 focus:ring-blue-500">Create Voucher</button>
                </form>
            </div>

            <!-- Existing Vouchers Table -->
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-center text-2xl font-bold mb-6">Existing Vouchers</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white rounded-lg shadow-md">
                        <thead>
                            <tr>
                                <th class="px-4 py-2 bg-gray-100 text-gray-700 text-center">ID</th>
                                <th class="px-4 py-2 bg-gray-100 text-gray-700 text-center">Voucher Name</th>
                                <th class="px-4 py-2 bg-gray-100 text-gray-700 text-center">Duration</th>
                                <th class="px-4 py-2 bg-gray-100 text-gray-700 text-center">Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($vouchers as $voucher)
                                <tr class="border-b">
                                    <td class="px-4 py-2 text-center">{{ $voucher->id }}</td>
                                    <td class="px-4 py-2 text-center">{{ $voucher->voucher_name }}</td>
                                    <td class="px-4 py-2 text-center">{{ $voucher->duration }} min</td>
                                    <td class="px-4 py-2 text-center">Rp {{ number_format($voucher->price, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-gray-500">No vouchers created yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const priceInput = document.querySelector('input[name="price"]');

            function removeFormatting(value) {
                return value.replace(/[^0-9]/g, ''); // Remove non-numeric characters
            }

            function formatToCurrency(value) {
                const cleanValue = removeFormatting(value);
                return new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                    minimumFractionDigits: 0,
                }).format(cleanValue);
            }

            function handlePriceInput(event) {
                const input = event.target;
                const rawValue = removeFormatting(input.value); // Only keep numbers
                const formattedValue = formatToCurrency(rawValue);
                input.value = formattedValue;
            }

            function handlePriceSubmit(event) {
                const rawValue = removeFormatting(priceInput.value); // Strip formatting
                priceInput.value = rawValue; // Set raw value before form submission
            }

            priceInput.addEventListener('input', handlePriceInput);
            const form = priceInput.closest('form');
            form.addEventListener('submit', handlePriceSubmit);
        });
    </script>
</body>
</html>
