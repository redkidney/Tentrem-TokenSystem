<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Charging Station Token System</title>
    @vite('resources/css/app.css')
    @vite('resources/js/app.js')
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.10.5/dist/cdn.min.js" defer></script>
</head>

<body class="bg-gray-50 min-h-screen">
    <div class="flex h-screen bg-gray-100">
        <!-- Sidebar -->
        <aside class="w-64 bg-white shadow-md">
            <div class="p-4">
                <img src="{{ asset('images/Asset-1.png') }}" alt="Logo" class="w-32 mx-auto mb-6">
                <nav class="space-y-1">
                    <a href="{{ route('dashboard') }}" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-yellow-50 hover:text-yellow-800 {{ request()->routeIs('dashboard') ? 'bg-yellow-50 text-yellow-800' : 'text-gray-900' }}">
                        <i class="fas fa-home mr-2"></i>Dashboard
                    </a>
                    <a href="{{ route('registry') }}" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-yellow-50 hover:text-yellow-800 {{ request()->routeIs('registry') ? 'bg-yellow-50 text-yellow-800' : 'text-gray-900' }}">
                        <i class="fas fa-key mr-2"></i>Generate Token
                    </a>
                    <a href="{{ route('vouchers.create') }}" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-yellow-50 hover:text-yellow-800 {{ request()->routeIs('vouchers.create') ? 'bg-yellow-50 text-yellow-800' : 'text-gray-900' }}">
                        <i class="fas fa-ticket-alt mr-2"></i>Create Voucher
                    </a>
                    <a href="{{ route('reports.charging-sessions') }}" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-yellow-50 hover:text-yellow-800 {{ request()->routeIs('reports.charging-sessions') ? 'bg-yellow-50 text-yellow-800' : 'text-gray-900' }}">
                        <i class="fas fa-chart-bar mr-2"></i>View Reports
                    </a>
                </nav>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Navigation -->
            <header class="bg-white shadow-sm">
                <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8 flex justify-between items-center">
                    <h1 class="text-2xl font-semibold text-gray-900">Dashboard</h1>
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center space-x-2 text-gray-700 hover:text-gray-900 focus:outline-none">
                            <img class="h-8 w-8 rounded-full object-cover" src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&color=7F9CF5&background=EBF4FF" alt="{{ Auth::user()->name }}">
                            <span>{{ Auth::user()->name }}</span>
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-red-100 transition duration-150 ease-in-out">Logout</button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100">
                <div class="container mx-auto px-6 py-8">
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
            </main>
        </div>
    </div>

    @push('scripts')
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js" crossorigin="anonymous"></script>
    @endpush
</body>
</html>

