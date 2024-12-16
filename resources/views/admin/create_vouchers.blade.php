<x-admin-layout>
    <x-slot name="title">Create Voucher</x-slot>

    <div class="max-w-6xl mx-auto" x-data="{ 
        showEditModal: false,
        editingVoucher: null,
        editForm: {
            voucher_name: '',
            duration: '',
            price: ''
        },
        initializeEdit(voucher) {
            this.editingVoucher = voucher;
            this.editForm = {
                voucher_name: voucher.voucher_name,
                duration: voucher.duration,
                price: voucher.price
            };
            this.showEditModal = true;
        }
    }">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Voucher Creation Form -->
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h1 class="text-3xl font-bold mb-6">Create Voucher</h1>
                @if (session('success'))
                    <p class="text-center text-green-600 font-semibold mb-6">{{ session('success') }}</p>
                @endif

                <form method="POST" action="{{ route('vouchers.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label for="voucher_name" class="block text-gray-700">Voucher Name:</label>
                        <input type="text" name="voucher_name" required 
                               class="w-full p-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                               placeholder="Enter voucher name">
                    </div>

                    <div>
                        <label for="duration" class="block text-gray-700">Duration (in minutes):</label>
                        <input type="number" name="duration" value="60" required 
                               class="w-full p-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                               placeholder="Enter duration">
                    </div>

                    <div>
                        <label for="price" class="block text-gray-700">Price (in IDR):</label>
                        <input type="text" name="price" value="0" required 
                               class="w-full p-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                               placeholder="Enter price">
                    </div>

                    <button type="submit" 
                            class="w-full bg-green-500 text-white py-2 rounded-md hover:bg-green-600 active:bg-green-700 transition duration-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        Create Voucher
                    </button>
                </form>
            </div>

            <!-- Existing Vouchers Table -->
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-2xl font-bold mb-6">Existing Vouchers</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white rounded-lg shadow-md">
                        <thead>
                            <tr>
                                <th class="px-4 py-2 bg-gray-100 text-gray-700 text-center">ID</th>
                                <th class="px-4 py-2 bg-gray-100 text-gray-700 text-center">Voucher Name</th>
                                <th class="px-4 py-2 bg-gray-100 text-gray-700 text-center">Duration</th>
                                <th class="px-4 py-2 bg-gray-100 text-gray-700 text-center">Price</th>
                                <th class="px-4 py-2 bg-gray-100 text-gray-700 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($vouchers as $voucher)
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="px-4 py-2 text-center">{{ $voucher->id }}</td>
                                    <td class="px-4 py-2 text-center">{{ $voucher->voucher_name }}</td>
                                    <td class="px-4 py-2 text-center">{{ $voucher->duration }} min</td>
                                    <td class="px-4 py-2 text-center">Rp {{ number_format($voucher->price, 0, ',', '.') }}</td>
                                    <td class="px-4 py-2 text-center">
                                        <div class="flex justify-center space-x-2">
                                            <button @click="initializeEdit({{ $voucher }})"
                                                    class="bg-blue-500 text-white px-3 py-1 rounded-md hover:bg-blue-600 transition duration-200">
                                                Edit
                                            </button>
                                            <form action="{{ route('vouchers.destroy', $voucher) }}" method="POST" class="inline"
                                                  onsubmit="return confirm('Are you sure you want to delete this voucher?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" 
                                                        class="bg-red-500 text-white px-3 py-1 rounded-md hover:bg-red-600 transition duration-200">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-gray-500">No vouchers created yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Edit Modal -->
        <div x-show="showEditModal" 
             x-cloak
             class="fixed inset-0 z-50 overflow-y-auto"
             style="display: none;">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                    <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                </div>

                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form method="POST" 
                          :action="'/vouchers/' + editingVoucher.id"
                          class="space-y-4 p-6">
                        @csrf
                        @method('PUT')
                        
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-xl font-bold">Edit Voucher</h3>
                            <button type="button" @click="showEditModal = false" 
                                    class="text-gray-400 hover:text-gray-500">
                                <span class="sr-only">Close</span>
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div>
                            <label for="edit_voucher_name" class="block text-gray-700">Voucher Name:</label>
                            <input type="text" name="voucher_name" required 
                                   x-model="editForm.voucher_name"
                                   class="w-full p-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label for="edit_duration" class="block text-gray-700">Duration (in minutes):</label>
                            <input type="number" name="duration" required 
                                   x-model="editForm.duration"
                                   class="w-full p-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label for="edit_price" class="block text-gray-700">Price (in IDR):</label>
                            <input type="text" name="price" required 
                                   x-model="editForm.price"
                                   class="w-full p-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div class="flex justify-end space-x-3 pt-4">
                            <button type="button"
                                    @click="showEditModal = false"
                                    class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400 transition duration-200">
                                Cancel
                            </button>
                            <button type="submit"
                                    class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600 transition duration-200">
                                Update Voucher
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const priceInput = document.querySelector('input[name="price"]');

            function removeFormatting(value) {
                return value.replace(/[^0-9]/g, '');
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
                const rawValue = removeFormatting(input.value);
                const formattedValue = formatToCurrency(rawValue);
                input.value = formattedValue;
            }

            function handlePriceSubmit(event) {
                const rawValue = removeFormatting(priceInput.value);
                priceInput.value = rawValue;
            }

            priceInput.addEventListener('input', handlePriceInput);
            const form = priceInput.closest('form');
            form.addEventListener('submit', handlePriceSubmit);

            // Apply the same price formatting to the edit modal
            const editPriceInput = document.querySelector('input[name="edit_price"]');
            if (editPriceInput) {
                editPriceInput.addEventListener('input', handlePriceInput);
                editPriceInput.closest('form').addEventListener('submit', function() {
                    const rawValue = removeFormatting(editPriceInput.value);
                    editPriceInput.value = rawValue;
                });
            }
        });
    </script>
    @endpush
</x-admin-layout>