<footer class="bg-gray-800 text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col md:flex-row justify-between items-center">
            <div class="mb-4 md:mb-0">
                <a href="/" class="text-lg font-semibold">{{ config('app.name') }}</a>
            </div>
            <nav class="mb-4 md:mb-0">
                <ul class="flex flex-wrap justify-center space-x-4">
                    <li><a href="{{ route('about') }}" class="hover:text-gray-300">About Us</a></li>
                    <li><a href="{{ route('privacy') }}" class="hover:text-gray-300">Privacy</a></li>
                    <li><a href="{{ route('terms') }}" class="hover:text-gray-300">Terms &amp; Conditions</a></li>
                    <li><a href="https://wa.me/447706007407" class="hover:text-gray-300">Contact on WhatsApp</a></li>
                </ul>
            </nav>
        </div>
        <div class="text-center mt-8">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</footer>
