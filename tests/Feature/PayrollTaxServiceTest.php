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
}
