<x-admin-layout>
    <x-slot name="title">Generate Token</x-slot>

    <div class="p-6">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Token Generation Form -->
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h1 class="text-center text-3xl font-bold mb-6">Generate Charging Token</h1>
                @if (session('success'))
                    <p class="text-center text-green-600 font-semibold mb-6">{{ session('success') }}</p>
                @endif

                <form method="POST" action="{{ route('generate-token') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label for="guest_name" class="block text-gray-700">Guest Name:</label>
                        <input type="text" name="guest_name" required
                               class="w-full p-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                               placeholder="Enter guest name">
                    </div>

                    <div>
                        <label for="room_no" class="block text-gray-700">Room Number:</label>
                        <input type="text" name="room_no"
                               class="w-full p-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                               placeholder="Enter room number">
                    </div>

                    <div>
                        <label for="phone" class="block text-gray-700">Phone Number:</label>
                        <input type="text" name="phone"
                               class="w-full p-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                               placeholder="Enter phone number">
                    </div>

                    <div>
                        <label for="car_type" class="block text-gray-700">Car Type:</label>
                        <input type="text" name="car_type"
                               class="w-full p-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                               placeholder="Enter car type">
                    </div>

                    <div>
                        <label for="voucher_id" class="block text-gray-700">Voucher:</label>
                        <select name="voucher_id" required
                                class="w-full p-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                            <option value="" disabled selected>Select a voucher</option>
                            @foreach($vouchers as $voucher)
                                <option value="{{ $voucher->id }}">
                                    {{ $voucher->voucher_name }} - {{ $voucher->duration }} min - IDR{{ number_format($voucher->price, 2) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <button type="submit"
                                class="w-full bg-green-500 text-white py-2 rounded-md hover:bg-green-600 active:bg-green-700 disabled:bg-gray-300 transition duration-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            Generate Token
                        </button>
                    </div>
                </form>
            </div>

            <!-- Generated Tokens Section -->
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-center text-2xl font-bold mb-6">Generated Tokens</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white border border-gray-300 rounded-lg shadow-md">
                        <thead>
                            <tr class="bg-gray-100 text-gray-700">
                                <th class="px-4 py-2 text-left">Token</th>
                                <th class="px-4 py-2 text-left">Name</th>
                                <th class="px-4 py-2 text-left">Room No</th>
                                <th class="px-4 py-2 text-left">Expiry</th>
                                <th class="px-4 py-2 text-left">Duration</th>
                                {{-- <th class="px-4 py-2 text-left">Remaining</th> --}}
                                <th class="px-4 py-2 text-left">Used</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tokens as $token)
                                <tr class="border-b">
                                    <td class="px-4 py-2">{{ $token->token }}</td>
                                    <td class="px-4 py-2">{{ $token->guest_name }}</td>
                                    <td class="px-4 py-2">{{ $token->room_no }}</td>
                                    <td class="px-4 py-2">{{ $token->expiry->format('Y-m-d H:i') }}</td>
                                    <td class="px-4 py-2">{{ $token->duration }} min</td>
                                    {{-- <td class="px-4 py-2">{{ round($token->remaining_time/60, 1) }} min</td> --}}
                                    <td class="px-4 py-2">{{ $token->used ? 'Yes' : 'No' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        @if(session('tokenData'))
            <script>
            document.addEventListener("DOMContentLoaded", function() {
                const tokenData = @json(session('tokenData'));
                const currentDateTime = new Date();
                const formattedDateTime = currentDateTime.toLocaleString('en-GB', {
                    day: '2-digit', month: '2-digit', year: 'numeric',
                    hour: '2-digit', minute: '2-digit', hour12: true
                });

                const printWindow = window.open('', '', 'width=300,height=400');
                printWindow.document.write('<html><head><title>Print Token</title>');
                printWindow.document.write('<style>');
                printWindow.document.write(`
                    body { font-family: calibri; margin: 0; padding: 0; }
                    .header, .footer { text-align: center; font-weight: bold; font-size: 12px; margin-bottom: 10px; }
                    .divider { text-align: center; border-top: 1px dashed #000; font-size: 12px; margin: 10px 0; }
                    .token { text-align: center; font-size: 16px; font-weight: bold; margin: 20px 0; }
                    .details { text-align: left; font-size: 12px; line-height: 0.5; }
                `);
                printWindow.document.write('</style></head><body>');

                printWindow.document.write(`
                    <div class="header">HOTEL TENTREM YOGYAKARTA</div>
                    <div class="header">==EV TOKEN SYSTEM==</div>
                    <div class="divider"></div>
                    <div class="details">
                        <p>Guest: ${tokenData.guest_name}</p>
                        <p>Room No: ${tokenData.room_no}</p>
                        <br>
                        <p>Duration: ${tokenData.duration} minutes</p>
                        <p>Value: IDR ${tokenData.price}</p>
                    </div>
                    <div class="token">TOKEN: ${tokenData.token}</div>
                    <div class="details">
                        <p>Valid Until: ${tokenData.expiry}</p>
                    </div>
                    <div class="divider"></div>
                    <div class="details">
                        <p>Date: ${formattedDateTime}</p>
                    </div>
                    <div class="footer">Thank You!</div>
                `);

                printWindow.document.write('</body></html>');
                printWindow.document.close();
                printWindow.print();
                printWindow.close();
            });
            </script>
        @endif
    @endpush
</x-admin-layout>

