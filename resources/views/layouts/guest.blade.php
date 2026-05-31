<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        try {
            $siteName = app(\App\Settings\GeneralSettings::class)->site_name;
        } catch (\Throwable) {
            $siteName = config('app.name', 'Accounting');
        }
    @endphp

    <title>{{ $siteName }}</title>

    @vite('resources/css/app.css')
    @livewireStyles
</head>
<body class="font-sans antialiased bg-gray-100 dark:bg-gray-900">
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0">
        <div>
            <a href="{{ route('home') }}" class="flex items-center space-x-2">
                <x-logo class="h-12 w-auto"/>
                <span class="text-xl font-bold text-gray-900 dark:text-white">{{ $siteName }}</span>
            </a>
        </div>

        <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white dark:bg-gray-800 shadow-md overflow-hidden sm:rounded-lg">
            {{ $slot }}
        </div>
    </div>

    @vite('resources/js/app.js')
    @livewireScriptConfig{{-- Livewire 4 replacement for @livewireScripts --}}
</body>
</html>
