<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected \App\Models\Invoice $invoice)
    {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Payment Reminder - Invoice #' . $this->invoice->invoice_id)
            ->greeting('Hello ' . $this->invoice->customer->customer_name)
            ->line('This is a reminder that payment for Invoice #' . $this->invoice->invoice_id . ' is overdue.')
            ->line('Amount due: $' . number_format($this->invoice->getTotalWithTax(), 2))
            ->line('Due date: ' . $this->invoice->invoice_date->format('Y-m-d'))
            ->action('View Invoice', url('/invoices/' . $this->invoice->invoice_id))
            ->line('Thank you for your business!');
    }
}
