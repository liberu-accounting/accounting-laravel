@extends('layouts.app')

@section('content')

{{-- Hero --}}
<section class="bg-white dark:bg-gray-900">
    <div class="max-w-(--breakpoint-xl) mx-auto px-4 py-24 lg:py-32 text-center">
        <h1 class="text-4xl font-extrabold tracking-tight text-gray-900 dark:text-white md:text-5xl lg:text-6xl">
            Smart Accounting.<br>
            <span class="text-blue-600 dark:text-blue-500">Built for Your Business.</span>
        </h1>
        <p class="mt-6 text-lg text-gray-600 dark:text-gray-300 max-w-2xl mx-auto">
            Manage invoices, track expenses, reconcile bank feeds, and generate tax-ready reports — all in one place.
        </p>
        <div class="mt-10 flex flex-col sm:flex-row gap-4 justify-center">
            @auth
                <a href="/app"
                    class="inline-flex items-center justify-center px-8 py-3 text-base font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:ring-4 focus:ring-blue-300">
                    Go to Dashboard
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                </a>
            @else
                <a href="{{ route('register') }}"
                    class="inline-flex items-center justify-center px-8 py-3 text-base font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:ring-4 focus:ring-blue-300">
                    Start for Free
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                </a>
                <a href="{{ route('login') }}"
                    class="inline-flex items-center justify-center px-8 py-3 text-base font-medium text-gray-900 bg-white border border-gray-200 rounded-lg hover:bg-gray-100 focus:ring-4 focus:ring-gray-100">
                    Log In
                </a>
            @endauth
        </div>
    </div>
</section>

{{-- Features --}}
<section class="bg-gray-50 dark:bg-gray-800 py-16 lg:py-24">
    <div class="max-w-(--breakpoint-xl) mx-auto px-4">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Everything you need to run your finances</h2>
            <p class="mt-4 text-gray-600 dark:text-gray-400">A complete accounting suite designed for modern businesses.</p>
        </div>

        <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
            <div class="bg-white dark:bg-gray-700 p-6 rounded-xl shadow-sm">
                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Invoicing & Billing</h3>
                <p class="text-gray-600 dark:text-gray-300">Create professional invoices, send automated payment reminders, and track outstanding balances in real time.</p>
            </div>

            <div class="bg-white dark:bg-gray-700 p-6 rounded-xl shadow-sm">
                <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Bank Reconciliation</h3>
                <p class="text-gray-600 dark:text-gray-300">Connect your bank feeds and automatically match transactions to keep your books balanced effortlessly.</p>
            </div>

            <div class="bg-white dark:bg-gray-700 p-6 rounded-xl shadow-sm">
                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Financial Reports</h3>
                <p class="text-gray-600 dark:text-gray-300">Generate P&L statements, balance sheets, cash flow reports, and VAT returns with a single click.</p>
            </div>

            <div class="bg-white dark:bg-gray-700 p-6 rounded-xl shadow-sm">
                <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900 rounded-lg flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Expense Tracking</h3>
                <p class="text-gray-600 dark:text-gray-300">Capture and categorise expenses, manage supplier bills, and keep spending under control across your organisation.</p>
            </div>

            <div class="bg-white dark:bg-gray-700 p-6 rounded-xl shadow-sm">
                <div class="w-12 h-12 bg-teal-100 dark:bg-teal-900 rounded-lg flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-teal-600 dark:text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Multi-Team Support</h3>
                <p class="text-gray-600 dark:text-gray-300">Manage multiple companies or departments with isolated data, role-based permissions, and team switching built in.</p>
            </div>

            <div class="bg-white dark:bg-gray-700 p-6 rounded-xl shadow-sm">
                <div class="w-12 h-12 bg-red-100 dark:bg-red-900 rounded-lg flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Tax & Compliance</h3>
                <p class="text-gray-600 dark:text-gray-300">Stay compliant with built-in VAT returns, HMRC submissions, and audit logs that give you full visibility.</p>
            </div>
        </div>
    </div>
</section>

{{-- CTA --}}
<section class="bg-blue-600 dark:bg-blue-700 py-16">
    <div class="max-w-(--breakpoint-xl) mx-auto px-4 text-center">
        <h2 class="text-3xl font-bold text-white mb-4">Ready to take control of your finances?</h2>
        <p class="text-blue-100 text-lg mb-8">Join businesses already using {{ config('app.name') }} to simplify their accounting.</p>
        @guest
            <a href="{{ route('register') }}"
                class="inline-flex items-center justify-center px-8 py-3 text-base font-medium text-blue-600 bg-white rounded-lg hover:bg-blue-50 focus:ring-4 focus:ring-blue-300">
                Create your free account
            </a>
        @endguest
        @auth
            <a href="/app"
                class="inline-flex items-center justify-center px-8 py-3 text-base font-medium text-blue-600 bg-white rounded-lg hover:bg-blue-50 focus:ring-4 focus:ring-blue-300">
                Open Dashboard
            </a>
        @endauth
    </div>
</section>

@endsection
