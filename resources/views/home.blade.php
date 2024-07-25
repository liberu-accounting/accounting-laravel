@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-4xl font-bold mb-4">Welcome to {{ config('app.name') }}</h1>
    <p class="mb-8">This is the home page of our application.</p>
    
    <div class="space-x-4">
        <a href="{{ route('login') }}" class="inline-block bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded">
            Log in
        </a>
        <a href="{{ route('register') }}" class="inline-block bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded">
            Register
        </a>
    </div>
</div>
@endsection