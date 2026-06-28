<?php

declare(strict_types=1);
namespace App\Console\Commands;

use App\Models\Expense;
use App\Models\Invoice;
use Illuminate\Console\Command;

class ProcessRecurringTransactions extends Command
{
    #[\Override]
    protected $signature = 'recurring:process';
    #[\Override]
    protected $description = 'Process all recurring invoices and expenses';

    public function handle(): void
    {
        Invoice::where('is_recurring', true)->each(function ($invoice): void {
            $invoice->generateRecurring();
        });

        Expense::where('is_recurring', true)->each(function ($expense): void {
            $expense->generateRecurring();
        });

        $this->info('Recurring transactions processed successfully');
    }
}
