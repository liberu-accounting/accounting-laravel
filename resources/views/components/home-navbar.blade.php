@php
    try {
        $siteName = app(\App\Settings\GeneralSettings::class)->site_name;
    } catch (\Throwable) {
        $siteName = config('app.name', 'Accounting');
    }

    $dashboardUrl = null;
    $role = null;
    if (auth()->check()) {
        $authUser = auth()->user();
        $role = $authUser->getRoleNames()->first() ?? 'user';
        $dashboardUrl = $role === 'admin' ? '/admin' : '/app';
    }

    try {
        $menuHtml = app(\App\Services\MenuService::class)->buildMenu();
    } catch (\Throwable) {
        $menuHtml = '';
    }
@endphp
<nav class="bg-white border-b border-gray-200 dark:bg-gray-900">
    <div class="max-w-(--breakpoint-xl) flex flex-wrap items-center justify-between mx-auto p-4">
        <a href="{{ route('home') }}" class="flex items-center space-x-3 rtl:space-x-reverse">
            <x-logo/>
            <span class="self-center text-2xl font-semibold whitespace-nowrap dark:text-white">
                {{ $siteName }}
            </span>
        </a>

        <div class="items-center hidden justify-between w-full lg:flex lg:w-auto" id="navbar-cta">
            <ul class="flex flex-col font-medium p-4 md:p-0 mt-4 border border-gray-100 rounded-lg bg-gray-50 md:space-x-8 rtl:space-x-reverse md:flex-row md:mt-0 md:border-0 md:bg-white dark:bg-gray-800 md:dark:bg-gray-900 dark:border-gray-700">
                {!! $menuHtml !!}
            </ul>
        </div>

        <div class="flex items-center space-x-3 rtl:space-x-reverse">
            @if (auth()->check())
                <a href="{{ $dashboardUrl }}"
                    class="text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-4 py-2 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800">
                    {{ ucfirst($role) }} Dashboard
                </a>
            @else
                <a href="{{ route('login') }}"
                    class="text-gray-800 dark:text-white hover:bg-gray-50 focus:ring-4 focus:ring-gray-300 font-medium rounded-lg text-sm px-4 py-2 dark:hover:bg-gray-700 focus:outline-none dark:focus:ring-gray-800">
                    Log in
                </a>
                <a href="{{ route('register') }}"
                    class="text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-4 py-2 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800">
                    Get Started
                </a>
            @endif

            <button id="menuToggleButton" type="button"
                class="inline-flex items-center p-2 w-10 h-10 justify-center text-sm text-gray-500 rounded-lg lg:hidden hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:text-gray-400 dark:hover:bg-gray-700 dark:focus:ring-gray-600"
                aria-controls="menuToggle" aria-expanded="false">
                <span class="sr-only">Open main menu</span>
                <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 17 14">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 1h15M1 7h15M1 13h15"/>
                </svg>
            </button>
        </div>
    </div>

    <div class="hidden lg:hidden" id="menuToggle">
        <ul class="flex flex-col font-medium mt-4 rounded-lg bg-gray-50 dark:bg-gray-800 dark:border-gray-700 p-4">
            {!! $menuHtml !!}
        </ul>
    </div>
</nav>

<script>
    document.getElementById('menuToggleButton')?.addEventListener('click', function() {
        document.getElementById('menuToggle')?.classList.toggle('hidden');
    });
</script>
