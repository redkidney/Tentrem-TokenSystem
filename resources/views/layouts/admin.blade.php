<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Dashboard' }} | Charging Station Token System</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite('resources/css/app.css')
    @vite('resources/js/app.js')
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js" crossorigin="anonymous"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        body { margin: 0; padding: 0; }
    </style>
    @stack('styles')
</head>
<body class="bg-gray-50">
    <div 
        x-data="{ 
            sidebarOpen: localStorage.getItem('sidebarOpen') === 'true' || false,
            toggleSidebar() {
                this.sidebarOpen = !this.sidebarOpen;
                localStorage.setItem('sidebarOpen', this.sidebarOpen);
            }
        }" 
        class="min-h-screen flex"
    >
        <x-sidebar />
        <div 
            class="flex-1 flex flex-col transition-all duration-300" 
            :class="sidebarOpen ? 'ml-64' : 'ml-0'"
        >
            <x-top-navigation :title="$title ?? 'Dashboard'" />
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
                {{ $slot }}
            </main>
        </div>
    </div>
    @stack('scripts')
</body>
</html>

