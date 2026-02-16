<?php

return [
    /*
    |--------------------------------------------------------------------------
    | HMRC API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for HMRC Making Tax Digital (MTD) API integration
    | including RTI PAYE, VAT, and Corporation Tax submissions.
    |
    */

    'enabled' => env('HMRC_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Environment
    |--------------------------------------------------------------------------
    |
    | HMRC environment: 'sandbox' for testing, 'production' for live submissions
    |
    */
    'environment' => env('HMRC_ENVIRONMENT', 'sandbox'),

    /*
    |--------------------------------------------------------------------------
    | API Endpoints
    |--------------------------------------------------------------------------
    */
    'endpoints' => [
        'sandbox' => 'https://test-api.service.hmrc.gov.uk',
        'production' => 'https://api.service.hmrc.gov.uk',
    ],

    /*
    |--------------------------------------------------------------------------
    | OAuth 2.0 Credentials
    |--------------------------------------------------------------------------
    |
    | Client credentials for HMRC OAuth authentication
    |
    */
    'client_id' => env('HMRC_CLIENT_ID'),
    'client_secret' => env('HMRC_CLIENT_SECRET'),
    'server_token' => env('HMRC_SERVER_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Callback URL
    |--------------------------------------------------------------------------
    |
    | OAuth callback URL for HMRC authentication
    |
    */
    'callback_url' => env('HMRC_CALLBACK_URL', env('APP_URL') . '/hmrc/callback'),

    /*
    |--------------------------------------------------------------------------
    | VAT Configuration
    |--------------------------------------------------------------------------
    */
    'vat' => [
        'enabled' => env('HMRC_VAT_ENABLED', false),
        'periods' => ['monthly', 'quarterly', 'annually'],
    ],

    /*
    |--------------------------------------------------------------------------
    | PAYE Configuration
    |--------------------------------------------------------------------------
    */
    'paye' => [
        'enabled' => env('HMRC_PAYE_ENABLED', false),
        'rti_enabled' => env('HMRC_RTI_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Corporation Tax Configuration
    |--------------------------------------------------------------------------
    |
    | UK Corporation Tax rates and thresholds (can be updated annually)
    |
    */
    'corporation_tax' => [
        'enabled' => env('HMRC_CT_ENABLED', false),
        'rates' => [
            // As of April 2024
            'small_profits_rate' => 0.19,          // For profits £50,000 or less
            'main_rate' => 0.25,                   // For profits over £250,000
            'small_profits_threshold' => 50000,     // Lower threshold
            'marginal_relief_threshold' => 250000,  // Upper threshold
            'marginal_relief_fraction' => 3 / 200,  // Fraction for marginal relief calculation
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'enabled' => env('HMRC_LOGGING_ENABLED', true),
        'channel' => env('HMRC_LOG_CHANNEL', 'stack'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    */
    'timeout' => env('HMRC_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | RTI XML Schema Version
    |--------------------------------------------------------------------------
    |
    | HMRC RTI XML schema version and namespace
    |
    */
    'rti' => [
        'schema_version' => env('HMRC_RTI_SCHEMA_VERSION', '16-17'),
        'namespace_base' => 'http://www.govtalk.gov.uk/taxation/PAYE/RTI',
    ],
];
