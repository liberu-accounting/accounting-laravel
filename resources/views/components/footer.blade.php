@php
    try {
        $settings = app(\App\Settings\GeneralSettings::class);
        $siteName = $settings->site_name;
        $siteAddress = $settings->site_address ?? '';
        $sitePhone = $settings->site_phone ?? '';
        $footerCopyright = $settings->footer_copyright ?? '© ' . date('Y') . ' ' . config('app.name');
        $facebookUrl = $settings->facebook_url ?? '#';
        $twitterUrl = $settings->twitter_url ?? '#';
        $githubUrl = $settings->github_url ?? '#';
        $youtubeUrl = $settings->youtube_url ?? '#';
    } catch (\Throwable) {
        $siteName = config('app.name', 'Accounting');
        $siteAddress = '';
        $sitePhone = '';
        $footerCopyright = '© ' . date('Y') . ' ' . config('app.name');
        $facebookUrl = '#';
        $twitterUrl = '#';
        $githubUrl = '#';
        $youtubeUrl = '#';
    }
@endphp
<footer class="p-4 bg-white sm:p-6 dark:bg-gray-800">
    <div class="mx-auto max-w-(--breakpoint-xl)">
        <div class="md:flex md:justify-between">
            <div class="mb-6 md:mb-0">
                <a href="{{ route('home') }}" class="flex items-center">
                    <x-logo/>
                    <span class="self-center text-2xl font-semibold whitespace-nowrap dark:text-white ml-2">
                        {{ $siteName }}
                    </span>
                </a>
            </div>
            <div class="grid grid-cols-2 gap-8 sm:gap-6 sm:grid-cols-3">
                <div></div>
                <div></div>
                @if($siteAddress || $sitePhone)
                <div>
                    <h2 class="mb-6 text-sm font-semibold text-gray-900 uppercase dark:text-white">Contact Details</h2>
                    @if($siteAddress)
                        <span class="text-xs text-gray-600">Address: {{ $siteAddress }}</span><br>
                    @endif
                    @if($sitePhone)
                        <span class="text-xs text-gray-600">Phone: {{ $sitePhone }}</span><br>
                    @endif
                </div>
                @endif
            </div>
        </div>
        <hr class="my-6 border-gray-200 sm:mx-auto dark:border-gray-700 lg:my-8"/>
        <div class="sm:flex sm:items-center sm:justify-between">
            <span class="text-sm text-gray-500 sm:text-center dark:text-gray-400">
                {{ $footerCopyright }}
            </span>
            <div class="flex mt-4 space-x-6 sm:justify-center sm:mt-0">
                @if($facebookUrl && $facebookUrl !== '#')
                <a href="{{ $facebookUrl }}" class="text-gray-500 hover:text-gray-900 dark:hover:text-white">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path fill-rule="evenodd" d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z" clip-rule="evenodd"/>
                    </svg>
                </a>
                @endif
                @if($githubUrl && $githubUrl !== '#')
                <a href="{{ $githubUrl }}" class="text-gray-500 hover:text-gray-900 dark:hover:text-white">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd"/>
                    </svg>
                </a>
                @endif
            </div>
        </div>
    </div>
</footer>
