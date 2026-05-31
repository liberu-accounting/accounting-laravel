<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_has_customer_relationship(): void
    {
        $this->assertNotNull((new Invoice)->customer());
    }

    public function test_invoice_has_time_entries_relationship(): void
    {
        $this->assertNotNull((new Invoice)->timeEntries());
    }

    public function test_invoice_has_credit_memos_relationship(): void
    {
        $this->assertNotNull((new Invoice)->creditMemos());
    }

    public function test_invoice_total_amount_is_decimal_cast(): void
    {
        $invoice = new Invoice(['total_amount' => '1234.56']);

        $this->assertEquals('1234.56', $invoice->total_amount);
    }

    public function test_invoice_fillable_includes_payment_status(): void
    {
        $this->assertContains('payment_status', (new Invoice)->getFillable());
    }

    public function test_invoice_fillable_includes_invoice_number(): void
    {
        $this->assertContains('invoice_number', (new Invoice)->getFillable());
    }
}
