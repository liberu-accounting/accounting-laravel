<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Reporting currency
    |--------------------------------------------------------------------------
    | ISO code of the currency financial statements are presented in. When null,
    | the Currency flagged is_default is used. GeneralLedgerService resolves this.
    */
    'reporting_currency' => env('ACCOUNTING_REPORTING_CURRENCY'),
];
