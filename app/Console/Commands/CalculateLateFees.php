<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Invoice;
use Illuminate\Console\Command;

class CalculateLateFees extends Command
{
    #[\Override]
    protected $signature = 'invoices:calculate-late-fees';
    #[\Override]
    protected $description = 'Calculate late fees for overdue invoices';

    public function handle(): void
    {
        $overdueInvoices = Invoice::where('payment_status', '!=', 'paid')
            ->whereNotNull('due_date')
            ->get();

        foreach ($overdueInvoices as $invoice) {
            $invoice->calculateLateFee();
        }

        $this->info('Late fees calculated successfully.');
    }
}