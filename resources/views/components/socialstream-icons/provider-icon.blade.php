<div class="text-gray-900">
    @switch($provider)
        @case('bitbucket')
            <x-socialstream-icons.bitbucket {{ $attributes }} />
            @break

        @case('facebook')
            <x-socialstream-icons.facebook {{$attributes}} />
            @break

        @case('github')
            <x-socialstream-icons.github {{$attributes}} />
            @break

        @case('gitlab')
            <x-socialstream-icons.gitlab {{$attributes}} />
            @break

        @case('google')
            <x-socialstream-icons.google {{$attributes}} />
            @break

        @case('linkedin')
        @case('linkedinOpenId')
            <x-socialstream-icons.linkedin {{$attributes}} />
            @break

        @case('slack')
            <x-socialstream-icons.slack {{$attributes}} />
            @break

        @case('twitterOAuth1')
        @case('twitterOAuth2')
        @case('twitter')
            <x-socialstream-icons.twitter {{$attributes}} />
            @break
    @endswitch
</div>
