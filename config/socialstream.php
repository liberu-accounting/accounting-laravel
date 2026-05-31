<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Socialstream Middleware
    |--------------------------------------------------------------------------
    */

    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Socialstream Prompt
    |--------------------------------------------------------------------------
    */

    'prompt' => 'Or Login Via',

    /*
    |--------------------------------------------------------------------------
    | Socialstream Providers
    |--------------------------------------------------------------------------
    |
    | All providers enabled. twitter-oauth-1 is excluded because OAuth 1.0
    | requires live credentials even for redirect and cannot be tested without
    | real API keys.
    |
    */

    'providers' => [
        \JoelButcher\Socialstream\Providers::bitbucket(),
        \JoelButcher\Socialstream\Providers::facebook(),
        \JoelButcher\Socialstream\Providers::github(),
        \JoelButcher\Socialstream\Providers::gitlab(),
        \JoelButcher\Socialstream\Providers::google(),
        \JoelButcher\Socialstream\Providers::linkedin(),
        \JoelButcher\Socialstream\Providers::linkedinOpenId(),
        \JoelButcher\Socialstream\Providers::slack(),
        \JoelButcher\Socialstream\Providers::twitterOAuth2(),
    ],

    /*
    |--------------------------------------------------------------------------
    | Socialstream Component
    |--------------------------------------------------------------------------
    */

    'component' => null,

    /*
    |--------------------------------------------------------------------------
    | Features
    |--------------------------------------------------------------------------
    */

    'features' => [
        \JoelButcher\Socialstream\Features::rememberSession(),
        \JoelButcher\Socialstream\Features::providerAvatars(),
        \JoelButcher\Socialstream\Features::generateMissingEmails(),
        \JoelButcher\Socialstream\Features::createAccountOnFirstLogin(),
        \JoelButcher\Socialstream\Features::globalLogin(),
    ],

];
