<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Payroll tax tables (UK)
|--------------------------------------------------------------------------
| Annual statutory figures used by PayrollTaxService, keyed by tax year so
| multiple years coexist. All thresholds/amounts are annual. PayrollTaxService
| selects 'default_tax_year' unless asked for a specific year via forYear().
*/

return [
    'default_tax_year' => '2024-25',

    'tax_years' => [
        '2024-25' => [
            'paye' => [
                'personal_allowance' => 12570,
                'allowance_taper_threshold' => 100000,
                // Cumulative bands of TAXABLE income (after the allowance):
                'bands' => [
                    ['rate' => 0.20, 'upto' => 37700],    // basic
                    ['rate' => 0.40, 'upto' => 112570],   // higher (125 140 gross − 12 570 allowance)
                    ['rate' => 0.45, 'upto' => null],     // additional
                ],
            ],
            'national_insurance' => [
                'employee' => [
                    'primary_threshold' => 12570,
                    'upper_earnings_limit' => 50270,
                    'main_rate' => 0.08,
                    'upper_rate' => 0.02,
                ],
                'employer' => [
                    'secondary_threshold' => 9100,
                    'rate' => 0.138,
                ],
            ],
            'student_loan' => [
                'plan_1' => ['threshold' => 24990, 'rate' => 0.09],
                'plan_2' => ['threshold' => 27295, 'rate' => 0.09],
                'plan_4' => ['threshold' => 31395, 'rate' => 0.09],
                'postgraduate' => ['threshold' => 21000, 'rate' => 0.06],
            ],
        ],
    ],
];
