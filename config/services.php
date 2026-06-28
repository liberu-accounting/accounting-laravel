<?php

declare(strict_types=1);

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

    'xero' => [
        'client_id' => env('XERO_CLIENT_ID'),
        'client_secret' => env('XERO_CLIENT_SECRET'),
        'redirect_uri' => env('XERO_REDIRECT_URI'),
        'authorization_url' => env('XERO_AUTHORIZATION_URL', 'https://login.xero.com/identity/connect/authorize'),
        'token_url' => env('XERO_TOKEN_URL', 'https://identity.xero.com/connect/token'),
        'connections_url' => env('XERO_CONNECTIONS_URL', 'https://api.xero.com/connections'),
        'api_base_url' => env('XERO_API_BASE_URL', 'https://api.xero.com/api.xro/2.0'),
    ],

    'qbo' => [
        'client_id' => env('QBO_CLIENT_ID'),
        'client_secret' => env('QBO_CLIENT_SECRET'),
        'environment' => env('QBO_ENVIRONMENT', 'sandbox'), // sandbox, production
        'redirect_uri' => env('QBO_REDIRECT_URI'),
        'authorization_url' => env('QBO_AUTHORIZATION_URL', 'https://appcenter.intuit.com/connect/oauth2'),
        'token_url' => env('QBO_TOKEN_URL', 'https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer'),
        'api_base_url' => env('QBO_API_BASE_URL', 'https://sandbox-quickbooks.api.intuit.com'),
        'webhook_verifier_token' => env('QBO_WEBHOOK_VERIFIER_TOKEN'),
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

    'github' => [
        'client_id' => env('GITHUB_CLIENT_ID'),
        'client_secret' => env('GITHUB_CLIENT_SECRET'),
        'redirect' => env('GITHUB_REDIRECT_URI'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'facebook' => [
        'client_id' => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect' => env('FACEBOOK_REDIRECT_URI'),
    ],

    'gitlab' => [
        'client_id' => env('GITLAB_CLIENT_ID'),
        'client_secret' => env('GITLAB_CLIENT_SECRET'),
        'redirect' => env('GITLAB_REDIRECT_URI'),
    ],

    'bitbucket' => [
        'client_id' => env('BITBUCKET_CLIENT_ID'),
        'client_secret' => env('BITBUCKET_CLIENT_SECRET'),
        'redirect' => env('BITBUCKET_REDIRECT_URI'),
    ],

    'linkedin' => [
        'client_id' => env('LINKEDIN_CLIENT_ID'),
        'client_secret' => env('LINKEDIN_CLIENT_SECRET'),
        'redirect' => env('LINKEDIN_REDIRECT_URI'),
    ],

    'linkedin-openid' => [
        'client_id' => env('LINKEDIN_OPENID_CLIENT_ID'),
        'client_secret' => env('LINKEDIN_OPENID_CLIENT_SECRET'),
        'redirect' => env('LINKEDIN_OPENID_REDIRECT_URI'),
    ],

    'slack' => [
        'client_id' => env('SLACK_CLIENT_ID'),
        'client_secret' => env('SLACK_CLIENT_SECRET'),
        'redirect' => env('SLACK_REDIRECT_URI'),
    ],

    'twitter-oauth-2' => [
        'client_id' => env('TWITTER_OAUTH2_CLIENT_ID'),
        'client_secret' => env('TWITTER_OAUTH2_CLIENT_SECRET'),
        'redirect' => env('TWITTER_OAUTH2_REDIRECT_URI'),
    ],

];
