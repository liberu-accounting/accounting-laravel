<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Payroll tax tables (UK)
|--------------------------------------------------------------------------
| Annual statutory figures used by PayrollTaxService. Defaults are the
| 2024/25 tax year — update per tax year. All thresholds/amounts are annual.
*/

return [
    'tax_year' => '2024-25',

    'paye' => [
        // Standard personal allowance (tax code 1257L → 12 570). Income-tax bands
        // are amounts of TAXABLE income (after allowance).
        'personal_allowance' => 12570,
        // Allowance is withdrawn £1 for every £2 of income above this.
        'allowance_taper_threshold' => 100000,
        // Cumulative bands of TAXABLE income (after the allowance):
        'bands' => [
            ['rate' => 0.20, 'upto' => 37700],    // basic
            ['rate' => 0.40, 'upto' => 112570],   // higher (125 140 gross − 12 570 allowance)
            ['rate' => 0.45, 'upto' => null],     // additional
        ],
    ],

    'national_insurance' => [
        // Employee Class 1 (Category A): 8% between PT and UEL, 2% above UEL.
        'employee' => [
            'primary_threshold' => 12570,
            'upper_earnings_limit' => 50270,
            'main_rate' => 0.08,
            'upper_rate' => 0.02,
        ],
        // Employer: 13.8% above the secondary threshold.
        'employer' => [
            'secondary_threshold' => 9100,
            'rate' => 0.138,
        ],
    ],

    'student_loan' => [
        // plan => [threshold, rate]
        'plan_1' => ['threshold' => 24990, 'rate' => 0.09],
        'plan_2' => ['threshold' => 27295, 'rate' => 0.09],
        'plan_4' => ['threshold' => 31395, 'rate' => 0.09],
        'postgraduate' => ['threshold' => 21000, 'rate' => 0.06],
    ],
];
