<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Invoice;
use App\Models\User;
use App\Services\InvoicePostingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceLineItemsTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_total_rolls_up_from_line_items(): void
    {
        $invoice = Invoice::factory()->create(['total_amount' => 0]);

        $invoice->items()->create(['description' => 'Design', 'quantity' => 2, 'unit_price' => 50]);
        $invoice->items()->create(['description' => 'Hosting', 'quantity' => 1, 'unit_price' => 120]);

        $this->assertEquals(220.00, $invoice->fresh()->total_amount);
    }

    public function test_line_item_amount_auto_calculates(): void
    {
        $invoice = Invoice::factory()->create(['total_amount' => 0]);

        $item = $invoice->items()->create(['description' => 'Work', 'quantity' => 3, 'unit_price' => 25]);

        $this->assertEquals(75.00, $item->amount);
    }

    public function test_invoice_posts_balanced_journal_entry(): void
    {
        $this->actingAs(User::factory()->create());

        $receivable = Account::factory()->create(['account_type' => 'asset', 'normal_balance' => 'debit']);
        $revenue = Account::factory()->create(['account_type' => 'revenue', 'normal_balance' => 'credit']);

        $invoice = Invoice::factory()->create(['total_amount' => 0]);
        $invoice->items()->create(['account_id' => $revenue->id, 'description' => 'Service A', 'quantity' => 2, 'unit_price' => 100]);
        $invoice->items()->create(['account_id' => $revenue->id, 'description' => 'Service B', 'quantity' => 1, 'unit_price' => 50]);

        $entry = app(InvoicePostingService::class)->post($invoice->fresh(), $receivable);

        $this->assertTrue($entry->isBalanced());
        $this->assertEquals(250.00, $entry->total_debits);
        $this->assertEquals(250.00, $entry->total_credits);
        // one AR debit line + one credit line per item
        $this->assertSame(3, $entry->lines()->count());
        $this->assertEquals(250.00, $invoice->fresh()->total_amount);
    }

    public function test_line_item_carries_its_own_tax(): void
    {
        // Tax lives in exactly one place: per-line on invoice_items.tax_amount (P0-6c).
        $invoice = Invoice::factory()->create(['total_amount' => 0]);

        $item = $invoice->items()->create([
            'description' => 'Consulting',
            'quantity' => 2,
            'unit_price' => 100,
            'tax_amount' => 40,
        ]);

        $this->assertEquals(200.00, $item->amount);     // qty * unit_price, auto-calculated
        $this->assertEquals(40.00, $item->tax_amount);  // per-line tax preserved
    }

    public function test_invoice_total_with_tax_sums_line_item_tax(): void
    {
        // Creating + saving an invoice with taxed line items must not error, and
        // getTotalWithTax() must add per-line tax onto the rolled-up subtotal.
        $invoice = Invoice::factory()->create(['total_amount' => 0]);

        $invoice->items()->create(['description' => 'A', 'quantity' => 1, 'unit_price' => 100, 'tax_amount' => 20]);
        $invoice->items()->create(['description' => 'B', 'quantity' => 1, 'unit_price' => 50]); // untaxed line

        $invoice = $invoice->fresh();

        $this->assertEquals(150.00, $invoice->total_amount);       // subtotal rolled up from lines
        $this->assertEquals(170.00, $invoice->getTotalWithTax());  // 150 + 20 line tax
    }
}
