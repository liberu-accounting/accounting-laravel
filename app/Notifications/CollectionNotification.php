

<?php

namespace App\Notifications;

use App\Models\Customer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CollectionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $customer;

    public function __construct(Customer $customer)
    {
        $this->customer = $customer;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $totalOverdue = $this->customer->invoices()
            ->where('payment_status', 'pending')
            ->sum('total_amount');

        return (new MailMessage)
            ->subject('Important: Account Status Update')
            ->greeting('Dear ' . $this->customer->customer_name)
            ->line('Your account has been placed on credit hold due to overdue payments.')
            ->line('Total amount overdue: $' . number_format($totalOverdue, 2))
            ->line('Please contact our collections department immediately to resolve this issue.')
            ->action('View Account', url('/account'))
            ->line('Thank you for your prompt attention to this matter.');
    }
}