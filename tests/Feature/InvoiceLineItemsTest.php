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
}
