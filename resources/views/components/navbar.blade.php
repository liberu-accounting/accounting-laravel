<nav x-data="{ open: false }" class="flex-grow">
    <div class="hidden lg:flex lg:space-x-8">
        <a href="{{ route('home') }}" class="text-base font-medium text-white hover:text-gray-300">Home</a>
        <a href="{{ route('about') }}" class="text-base font-medium text-white hover:text-gray-300">About</a>
        <a href="{{ route('services') }}" class="text-base font-medium text-white hover:text-gray-300">Services</a>
        <a href="{{ route('contact') }}" class="text-base font-medium text-white hover:text-gray-300">Contact</a>
    </div>
    <div class="lg:hidden">
        <button @click="open = !open" class="text-white hover:text-gray-300">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path x-show="!open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                <path x-show="open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>
    <div x-show="open" class="lg:hidden mt-2 space-y-2">
        <a href="{{ route('home') }}" class="block text-base font-medium text-white hover:text-gray-300">Home</a>
        <a href="{{ route('about') }}" class="block text-base font-medium text-white hover:text-gray-300">About</a>
        <a href="{{ route('services') }}" class="block text-base font-medium text-white hover:text-gray-300">Services</a>
        <a href="{{ route('contact') }}" class="block text-base font-medium text-white hover:text-gray-300">Contact</a>
    </div>
</nav>