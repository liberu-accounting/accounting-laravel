

<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use Illuminate\Console\Command;

class CalculateLateFees extends Command
{
    protected $signature = 'invoices:calculate-late-fees';
    protected $description = 'Calculate late fees for overdue invoices';

    public function handle()
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