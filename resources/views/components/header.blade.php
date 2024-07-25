<header class="bg-gray-800">
    <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" aria-label="Top">
        <div class="w-full py-6 flex flex-wrap items-center justify-between">
            <div class="flex items-center">
                <a href="/">
                    <img class="h-10 w-auto" src="{{ asset('images/logo.svg') }}" alt="{{ config('app.name') }}">
                </a>
            </div>
            <div class="flex items-center">
                @include('components.navbar')
                <div class="ml-4 flex items-center space-x-4">
                    <a href="{{ route('login') }}" class="text-base font-medium text-white hover:text-gray-300">Log in</a>
                    <a href="{{ route('register') }}" class="inline-block bg-blue-500 py-2 px-4 border border-transparent rounded-md text-base font-medium text-white hover:bg-opacity-75">Register</a>
                </div>
            </div>
        </div>
    </nav>
</header>
