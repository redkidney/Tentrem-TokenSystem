<x-admin-layout>
    <x-slot name="title">Dashboard</x-slot>

    <div class="container mx-auto">
        <h2 class="text-3xl font-semibold text-gray-800 mb-6">Charging Station Token System</h2>
        
        <!-- Dashboard Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition duration-300">
                <h3 class="text-xl font-semibold text-gray-800 mb-2">Total Tokens</h3>
                <p class="text-3xl font-bold text-blue-600">{{ $totalTokens }}</p>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition duration-300">
                <h3 class="text-xl font-semibold text-gray-800 mb-2">Total Vouchers</h3>
                <p class="text-3xl font-bold text-green-600">{{ $activeVouchers }}</p>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-xl font-semibold text-gray-800 mb-4">Recent Activity</h3>
            <ul class="divide-y divide-gray-200">
                @foreach($recentActivities as $activity)
                    <li class="py-3">
                        <div class="flex items-center">
                            @if($activity['type'] === 'charging_session')
                                <span class="mr-2 text-blue-500"><i class="fas fa-bolt"></i></span>
                            @elseif($activity['type'] === 'token')
                                <span class="mr-2 text-green-500"><i class="fas fa-key"></i></span>
                            @elseif($activity['type'] === 'voucher')
                                <span class="mr-2 text-purple-500"><i class="fas fa-ticket-alt"></i></span>
                            @endif
                            <div>
                                <p class="text-gray-800">{{ $activity['message'] }}</p>
                                <p class="text-sm text-gray-500">{{ $activity['created_at']->diffForHumans() }}</p>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</x-admin-layout>