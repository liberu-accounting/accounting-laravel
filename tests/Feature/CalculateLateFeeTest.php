<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalculateLateFeeTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_charges_late_fee_on_overdue_invoice_without_fataling(): void
    {
        $invoice = Invoice::factory()->create([
            'total_amount' => 1000,
            'due_date' => now()->subDays(10)->toDateString(),
            'late_fee_percentage' => 5,
            'grace_period_days' => 0,
            'payment_status' => 'pending',
        ]);

        $this->artisan('invoices:calculate-late-fees')->assertExitCode(0);

        // 1000 * 5% = 50.00
        $this->assertEquals(50.00, $invoice->fresh()->late_fee_amount);
    }

    public function test_no_late_fee_before_due_date_plus_grace_period(): void
    {
        $invoice = Invoice::factory()->create([
            'total_amount' => 1000,
            'due_date' => now()->addDays(10)->toDateString(),
            'late_fee_percentage' => 5,
            'grace_period_days' => 0,
            'payment_status' => 'pending',
        ]);

        $this->assertEquals(0.0, $invoice->calculateLateFee());
        $this->assertEquals(0.00, $invoice->fresh()->late_fee_amount);
    }
}
