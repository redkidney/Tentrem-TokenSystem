<header class="bg-white shadow-sm">
    <div class="max-w-7xl mx-auto py-4 pl-16 sm:pl-20 pr-4 sm:pr-6 lg:pr-8 flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-900" x-show="'{{ $title }}' === 'Dashboard' || '{{ $title }}' === 'Charging Monitor'">
            {{ $title ?? 'Dashboard' }}
        </h1>
        <div x-show="'{{ $title }}' !== 'Dashboard'" class="w-8"></div>
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

