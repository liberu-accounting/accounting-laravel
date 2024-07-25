@extends('components.layouts.app')

@section('content')
<div class="flex flex-col min-h-screen">
    @include('components.header')
    
    <main class="flex-grow container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-4">Welcome to {{ config('app.name') }}</h1>
        <p class="text-lg text-gray-700">This is the home page of our accounting application.</p>
    </main>

    @include('components.footer')
</div>
@endsection