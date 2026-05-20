<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'plaid' => [
        'client_id' => env('PLAID_CLIENT_ID'),
        'secret' => env('PLAID_SECRET'),
        'environment' => env('PLAID_ENV', 'sandbox'), // sandbox, development, production
        'webhook_url' => env('PLAID_WEBHOOK_URL'),
        'webhook_verification_key' => env('PLAID_WEBHOOK_VERIFICATION_KEY'),
        'oauth_redirect_uri' => env('PLAID_OAUTH_REDIRECT_URI'),
    ],

    'revolut' => [
        'client_id' => env('REVOLUT_CLIENT_ID'),
        'client_secret' => env('REVOLUT_CLIENT_SECRET'),
        'environment' => env('REVOLUT_ENV', 'sandbox'), // sandbox, production
        'redirect_uri' => env('REVOLUT_REDIRECT_URI'),
        'webhook_secret' => env('REVOLUT_WEBHOOK_SECRET'),
    ],

    'wise' => [
        'client_id' => env('WISE_CLIENT_ID'),
        'client_secret' => env('WISE_CLIENT_SECRET'),
        'environment' => env('WISE_ENV', 'sandbox'), // sandbox, production
        'redirect_uri' => env('WISE_REDIRECT_URI'),
        'webhook_public_key' => env('WISE_WEBHOOK_PUBLIC_KEY'),
    ],

];
