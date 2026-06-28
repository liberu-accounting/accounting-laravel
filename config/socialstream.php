<?php

declare(strict_types=1);
use JoelButcher\Socialstream\Features;
use JoelButcher\Socialstream\Providers;

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
        Providers::bitbucket(),
        Providers::facebook(),
        Providers::github(),
        Providers::gitlab(),
        Providers::google(),
        Providers::linkedin(),
        Providers::linkedinOpenId(),
        Providers::slack(),
        Providers::twitterOAuth2(),
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
        Features::rememberSession(),
        Features::providerAvatars(),
        Features::generateMissingEmails(),
        Features::createAccountOnFirstLogin(),
        Features::globalLogin(),
    ],

];
