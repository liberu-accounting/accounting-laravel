<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\TaxRate;
use Tests\TestCase;

class TaxRateTest extends TestCase
{
    /**
     * Unsaved model: calculateTax() reads only cast attributes, so no DB is
     * needed (and the tax_rates table has no is_compound/is_active columns).
     */
    private function rate(float $rate, bool $active = true, bool $compound = false): TaxRate
    {
        $r = new TaxRate;
        $r->rate = $rate;
        $r->is_active = $active;
        $r->is_compound = $compound;

        return $r;
    }

    public function test_inactive_rate_returns_zero(): void
    {
        // Inactive short-circuits before any maths and returns int 0.
        $this->assertSame(0, $this->rate(20, active: false)->calculateTax(100, 50));
    }

    public function test_simple_non_compound_is_amount_times_rate(): void
    {
        // 100 * 20/100 = 20
        $this->assertEqualsWithDelta(20.0, $this->rate(20)->calculateTax(100), 0.0001);
    }

    public function test_non_compound_ignores_previous_taxes(): void
    {
        // previousTaxes must be irrelevant when not compound: still 100 * 20/100.
        $this->assertEqualsWithDelta(20.0, $this->rate(20)->calculateTax(100, 999), 0.0001);
    }

    public function test_compound_adds_previous_taxes_to_base(): void
    {
        // (100 + 50) * 20/100 = 30
        $this->assertEqualsWithDelta(30.0, $this->rate(20, compound: true)->calculateTax(100, 50), 0.0001);
    }

    public function test_zero_rate_returns_zero(): void
    {
        $this->assertEqualsWithDelta(0.0, $this->rate(0)->calculateTax(100), 0.0001);
    }

    public function test_zero_amount_returns_zero(): void
    {
        $this->assertEqualsWithDelta(0.0, $this->rate(20)->calculateTax(0), 0.0001);
    }
}
