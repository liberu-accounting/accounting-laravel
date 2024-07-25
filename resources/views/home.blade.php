<x-layouts.app>
    <x-home-navbar />
    
    <x-home-header />
    
    <main class="container mx-auto mt-8 px-4">
        <h1 class="text-4xl font-bold mb-6">Welcome to {{ config('app.name') }}</h1>
        
        <p class="text-lg mb-6">This is the home page of our application. Feel free to explore!</p>
        
        <div class="flex space-x-4">
            <a href="{{ route('login') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Login
            </a>
            <a href="{{ route('register') }}" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                Register
            </a>
        </div>
    </main>
    
    <x-footer />
</x-layouts.app>