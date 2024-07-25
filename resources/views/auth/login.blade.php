@extends('components.layouts.app')

@section('content')
<div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
    <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
        <h2 class="text-2xl font-bold mb-4 text-center">{{ __('Login') }}</h2>
        <div class="mb-4 text-sm text-gray-600">
            {{ __('Please sign in to access the admin panel.') }}
        </div>

        <form method="POST" action="{{ route('login') }}
