<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\PayrollTaxService;
use Tests\TestCase;

class PayrollTaxServiceTest extends TestCase
{
    private function tax(): PayrollTaxService
    {
        return app(PayrollTaxService::class);
    }

    public function test_paye_basic_rate(): void
    {
        // £30,000, code 1257L → taxable 17,430 @ 20% = 3,486.00
        $this->assertSame(3486.00, $this->tax()->incomeTax(30000));
    }

    public function test_paye_higher_rate_spans_two_bands(): void
    {
        // £60,000 → taxable 47,430: 37,700@20% (7,540) + 9,730@40% (3,892) = 11,432.00
        $this->assertSame(11432.00, $this->tax()->incomeTax(60000));
    }

    public function test_paye_below_allowance_is_zero(): void
    {
        $this->assertSame(0.0, $this->tax()->incomeTax(10000));
    }

    public function test_employee_ni(): void
    {
        // (30,000 − 12,570) × 8% = 1,394.40
        $this->assertSame(1394.40, $this->tax()->employeeNi(30000));
        // £60,000: (50,270−12,570)×8% + (60,000−50,270)×2% = 3,016 + 194.60 = 3,210.60
        $this->assertSame(3210.60, $this->tax()->employeeNi(60000));
    }

    public function test_employer_ni(): void
    {
        // (30,000 − 9,100) × 13.8% = 2,884.20
        $this->assertSame(2884.20, $this->tax()->employerNi(30000));
    }

    public function test_student_loan_plan_2(): void
    {
        // (30,000 − 27,295) × 9% = 243.45
        $this->assertSame(243.45, $this->tax()->studentLoan(30000, 'plan_2'));
        $this->assertSame(0.0, $this->tax()->studentLoan(30000, null));
    }

    public function test_compute_bundles_all_figures(): void
    {
        $r = $this->tax()->compute(30000, '1257L', 'plan_2');

        $this->assertSame(3486.00, $r['income_tax']);
        $this->assertSame(1394.40, $r['employee_ni']);
        $this->assertSame(2884.20, $r['employer_ni']);
        $this->assertSame(243.45, $r['student_loan']);
        // net = gross − income tax − employee NI − student loan (employer NI is not deducted)
        $this->assertSame(24876.15, $r['net']);
    }

    public function test_allowance_taper_reduces_allowance_over_100k(): void
    {
        // £110,000: allowance 12,570 − floor((110,000−100,000)/2)=5,000 → 7,570.
        // taxable 102,430: 37,700@20% (7,540) + 64,730@40% (25,892) = 33,432.00
        $this->assertSame(33432.00, $this->tax()->incomeTax(110000));
    }

    public function test_allowance_fully_tapered_and_additional_rate_band(): void
    {
        // £130,000: allowance 12,570 − floor(30,000/2)=15,000 → clamped to 0.
        // taxable 130,000: 37,700@20% (7,540) + 74,870@40% (29,948) + 17,430@45% (7,843.5)
        $this->assertSame(45331.50, $this->tax()->incomeTax(130000));
    }

    public function test_non_numeric_tax_codes_fall_back_to_personal_allowance(): void
    {
        // BR/D0/NT have no numeric part → configured personal_allowance (12,570),
        // same result as 1257L for the same gross.
        $expected = $this->tax()->incomeTax(30000, '1257L');
        $this->assertSame($expected, $this->tax()->incomeTax(30000, 'BR'));
        $this->assertSame($expected, $this->tax()->incomeTax(30000, 'D0'));
        $this->assertSame($expected, $this->tax()->incomeTax(30000, 'NT'));
    }

    public function test_student_loan_plan_variants(): void
    {
        // plan_1: (30,000 − 24,990) × 9% = 450.90
        $this->assertSame(450.90, $this->tax()->studentLoan(30000, 'plan_1'));
        // plan_4: (40,000 − 31,395) × 9% = 774.45
        $this->assertSame(774.45, $this->tax()->studentLoan(40000, 'plan_4'));
        // postgraduate: (30,000 − 21,000) × 6% = 540.00
        $this->assertSame(540.00, $this->tax()->studentLoan(30000, 'postgraduate'));
    }

    public function test_student_loan_unknown_and_empty_plan_are_zero(): void
    {
        $this->assertSame(0.0, $this->tax()->studentLoan(30000, 'plan_9'));
        $this->assertSame(0.0, $this->tax()->studentLoan(30000, ''));
    }

    public function test_student_loan_below_threshold_is_zero(): void
    {
        // plan_4 threshold is 31,395; below it clamps to 0.
        $this->assertSame(0.0, $this->tax()->studentLoan(30000, 'plan_4'));
    }

    public function test_employee_ni_below_threshold_is_zero(): void
    {
        // £10,000 is below the primary threshold (12,570) → 0.
        $this->assertSame(0.0, $this->tax()->employeeNi(10000));
    }

    public function test_employer_ni_below_threshold_is_zero(): void
    {
        // £5,000 is below the secondary threshold (9,100) → 0.
        $this->assertSame(0.0, $this->tax()->employerNi(5000));
    }
}
