<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\PayrollTaxService;
use Tests\TestCase;

class PayrollTaxPeriodTest extends TestCase
{
    private function tax(): PayrollTaxService
    {
        return app(PayrollTaxService::class);
    }

    public function test_monthly_apportionment(): void
    {
        // £2,500/month → £30,000/yr. Annual PAYE 3,486 / EE NI 1,394.40.
        $r = $this->tax()->computeForPeriod(2500, 'monthly');

        $this->assertSame(290.50, $r['income_tax']);   // 3,486 / 12
        $this->assertSame(116.20, $r['employee_ni']);  // 1,394.40 / 12
        $this->assertSame(2093.30, $r['net']);         // 25,119.60 / 12
    }

    public function test_weekly_apportionment_uses_52_periods(): void
    {
        $r = $this->tax()->computeForPeriod(1000, 'weekly');           // £52,000/yr
        $annual = $this->tax()->compute(52000);

        $this->assertSame(round($annual['income_tax'] / 52, 2), $r['income_tax']);
    }

    public function test_w1m1_code_parses_like_cumulative_for_allowance(): void
    {
        $this->assertSame($this->tax()->incomeTax(30000, '1257L'), $this->tax()->incomeTax(30000, '1257L W1'));
        $this->assertFalse($this->tax()->isCumulative('1257L W1'));
        $this->assertTrue($this->tax()->isCumulative('1257L'));
    }

    public function test_integer_periods_match_the_named_frequency(): void
    {
        // Passing the integer 12 must behave identically to 'monthly'.
        $byName = $this->tax()->computeForPeriod(2500, 'monthly');
        $byInt = $this->tax()->computeForPeriod(2500, 12);

        $this->assertSame($byName, $byInt);
        $this->assertSame(290.50, $byInt['income_tax']);
    }

    public function test_fortnightly_apportionment_uses_26_periods(): void
    {
        // £1,000/fortnight → £26,000/yr, annual figures divided by 26.
        $annual = $this->tax()->compute(26000);
        $r = $this->tax()->computeForPeriod(1000, 'fortnightly');

        $this->assertSame(round($annual['income_tax'] / 26, 2), $r['income_tax']);
        $this->assertSame(round($annual['net'] / 26, 2), $r['net']);
    }

    public function test_unknown_frequency_falls_back_to_12_periods(): void
    {
        // An unrecognised frequency key defaults to 12 (?? 12), i.e. monthly.
        $monthly = $this->tax()->computeForPeriod(2500, 'monthly');
        $unknown = $this->tax()->computeForPeriod(2500, 'quarterly');

        $this->assertSame($monthly, $unknown);
    }

    public function test_for_year_selects_the_right_tables(): void
    {
        // A fabricated year with a lower basic rate (10%) → less tax.
        config()->set('payroll.tax_years.2099-00', config('payroll.tax_years.2024-25'));
        config()->set('payroll.tax_years.2099-00.paye.bands', [
            ['rate' => 0.10, 'upto' => 37700],
            ['rate' => 0.40, 'upto' => 112570],
            ['rate' => 0.45, 'upto' => null],
        ]);

        $base = $this->tax()->incomeTax(30000);                 // default 2024-25, taxable 17,430 @ 20%
        $future = $this->tax()->forYear('2099-00')->incomeTax(30000); // @ 10%

        $this->assertSame(3486.00, $base);
        $this->assertSame(1743.00, $future); // 17,430 × 10%
    }
}
