<footer class="bg-green-900 text-white py-4">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center">
            <div>
                <a href="/" class="text-lg font-semibold">{{ config('app.name') }}</a>
            </div>
        </div>
        <div class="text-center mt-2">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</footer>
