<div x-data="{ sidebarOpen: false }" class="relative">
    <!-- Menu Toggle Button -->
    <button 
        @click="sidebarOpen = !sidebarOpen"
        class="fixed top-4 left-4 z-50 p-3 bg-white rounded shadow-md hover:bg-gray-100 flex items-center justify-center"
        style="min-width: 44px; min-height: 44px;"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
    </button>

    <!-- Sidebar -->
    <aside 
        x-cloak
        :class="sidebarOpen ? 'translate-x-0' : '-translate-x-64'"
        class="fixed top-0 left-0 h-screen w-64 bg-white shadow-md transition-all duration-300 ease-in-out"
    >
        <div class="p-4 pt-20">
            <img 
                class="w-32 mx-auto mb-6"
                src="{{ asset('images/Asset-1.png') }}" 
                alt="Logo"
            >
            
            <nav class="space-y-1">
                <template x-for="(route, index) in [
                    { path: '{{ route('dashboard') }}', icon: 'home', label: 'Dashboard' },
                    { path: '{{ route('registry') }}', icon: 'key', label: 'Generate Token' },
                    { path: '{{ route('vouchers.create') }}', icon: 'ticket-alt', label: 'Create Voucher' },
                    { path: '{{ route('reports.charging-sessions') }}', icon: 'chart-bar', label: 'View Reports' },
                    { path: '{{ route('admin.monitor') }}', icon: 'desktop', label: 'Monitor' }
                ]">
                    <a :href="route.path" 
                       class="flex items-center py-2.5 px-4 rounded transition duration-200 hover:bg-yellow-50 hover:text-yellow-800 text-gray-900">
                        <i :class="`fas fa-${route.icon} mr-3`"></i>
                        <span x-text="route.label"></span>
                    </a>
                </template>
            </nav>
        </div>
    </aside>
</div>

