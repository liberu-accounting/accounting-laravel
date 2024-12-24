

<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\ReminderSetting;
use App\Notifications\PaymentReminderNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendPaymentReminders extends Command
{
    protected $signature = 'invoices:send-reminders';
    protected $description = 'Send payment reminders for overdue invoices';

    public function handle()
    {
        $settings = ReminderSetting::first();
        
        if (!$settings || !$settings->is_active) {
            $this->info('Reminder system is not active');
            return;
        }

        $overdueInvoices = Invoice::where('payment_status', 'pending')
            ->where('invoice_date', '<=', Carbon::now()->subDays($settings->days_before_reminder))
            ->where(function ($query) use ($settings) {
                $query->where('reminders_sent', '<', $settings->max_reminders)
                    ->orWhereNull('last_reminder_sent_at');
            })
            ->get();

        foreach ($overdueInvoices as $invoice) {
            if ($invoice->customer && $invoice->customer->email) {
                if (!$invoice->last_reminder_sent_at || 
                    Carbon::parse($invoice->last_reminder_sent_at)->addDays($settings->reminder_frequency_days)->isPast()) {
                    
                    $invoice->customer->notify(new PaymentReminderNotification($invoice));
                    
                    $invoice->reminders_sent++;
                    $invoice->last_reminder_sent_at = now();
                    $invoice->save();
                    
                    $this->info("Reminder sent for Invoice #{$invoice->invoice_id}");
                }
            }
        }
    }
}