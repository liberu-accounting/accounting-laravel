<?php
namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\Expense;
use Illuminate\Console\Command;

class ProcessRecurringTransactions extends Command
{
    protected $signature = 'recurring:process';
    protected $description = 'Process all recurring invoices and expenses';

    public function handle()
    {
        Invoice::where('is_recurring', true)->each(function ($invoice) {
            $invoice->generateRecurring();
        });

        Expense::where('is_recurring', true)->each(function ($expense) {
            $expense->generateRecurring();
        });

        $this->info('Recurring transactions processed successfully');
    }
}