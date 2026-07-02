<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\Invoice;
use App\Models\TaxRate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * End-to-end proof that the tax_rates columns exist and calculateTax() taxes
 * correctly through the live item save paths (P0-8). RefreshDatabase runs the
 * new migration, so a persisted rate that omits is_active must default active.
 */
class TaxRateCalculationTest extends TestCase
{
    use RefreshDatabase;

    public function test_persisted_active_rate_taxes_at_full_rate(): void
    {
        // is_active omitted → DB default true → 100 * 20/100 = 20.
        $rate = TaxRate::create(['name' => 'VAT', 'rate' => 20])->fresh();

        $this->assertTrue($rate->is_active);
        $this->assertEqualsWithDelta(20.0, $rate->calculateTax(100), 0.0001);
    }

    public function test_inactive_rate_returns_zero(): void
    {
        $rate = TaxRate::create(['name' => 'Retired VAT', 'rate' => 20, 'is_active' => false])->fresh();

        $this->assertSame(0, $rate->calculateTax(100, 50));
    }

    public function test_compound_rate_stacks_previous_taxes(): void
    {
        // (100 + 50) * 20/100 = 30.
        $rate = TaxRate::create(['name' => 'Compound', 'rate' => 20, 'is_compound' => true])->fresh();

        $this->assertEqualsWithDelta(30.0, $rate->calculateTax(100, 50), 0.0001);
    }

    public function test_bill_item_persists_non_zero_tax_amount(): void
    {
        $rate = TaxRate::create(['name' => 'VAT', 'rate' => 20]);
        $bill = Bill::factory()->create();

        $item = $bill->items()->create([
            'description' => 'Widget',
            'quantity' => 2,
            'unit_price' => 100,
            'tax_rate_id' => $rate->tax_rate_id,
        ])->fresh();

        $this->assertEqualsWithDelta(200.00, (float) $item->amount, 0.0001);
        $this->assertEqualsWithDelta(40.00, (float) $item->tax_amount, 0.0001);
    }

    public function test_invoice_item_persists_tax_amount(): void
    {
        $rate = TaxRate::create(['name' => 'VAT', 'rate' => 20]);
        $invoice = Invoice::factory()->create(['total_amount' => 0]);

        $item = $invoice->items()->create([
            'description' => 'Consulting',
            'quantity' => 2,
            'unit_price' => 100,
            'tax_rate_id' => $rate->tax_rate_id,
        ])->fresh();

        $this->assertEqualsWithDelta(200.00, (float) $item->amount, 0.0001);
        $this->assertEqualsWithDelta(40.00, (float) $item->tax_amount, 0.0001);
    }
}
