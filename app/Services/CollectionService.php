

<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Notifications\CollectionNotification;
use Carbon\Carbon;

class CollectionService
{
    public function flagDelinquentAccounts()
    {
        $customers = Customer::whereHas('invoices', function ($query) {
            $query->where('payment_status', 'pending')
                ->where('due_date', '<', Carbon::now()->subDays(90));
        })->get();

        foreach ($customers as $customer) {
            $customer->credit_hold = true;
            $customer->save();
            
            if ($customer->email) {
                $customer->notify(new CollectionNotification($customer));
            }
        }
    }

    public function generateCollectionReport()
    {
        return Customer::where('credit_hold', true)
            ->with(['invoices' => function ($query) {
                $query->where('payment_status', 'pending')
                    ->orderBy('due_date');
            }])
            ->get()
            ->map(function ($customer) {
                return [
                    'customer_name' => $customer->customer_name,
                    'total_overdue' => $customer->invoices->sum('total_amount'),
                    'oldest_invoice_date' => $customer->invoices->min('due_date'),
                    'number_of_invoices' => $customer->invoices->count(),
                    'contact_info' => [
                        'email' => $customer->customer_email,
                        'phone' => $customer->customer_phone
                    ]
                ];
            });
    }
}