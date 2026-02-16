<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillPayment extends Model
{
    use HasFactory;

    protected $primaryKey = 'payment_id';

    protected $fillable = [
        'bill_id',
        'payment_date',
        'amount',
        'payment_method',
        'reference_number',
        'bank_account_id',
        'notes',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    // Relationships
    public function bill()
    {
        return $this->belongsTo(Bill::class, 'bill_id', 'bill_id');
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankConnection::class, 'bank_account_id');
    }

    // Business Logic
    protected static function boot()
    {
        parent::boot();
        
        static::created(function ($payment) {
            // Recalculate bill's payment status
            if ($payment->bill) {
                $bill = $payment->bill;
                $bill->amount_paid = $bill->payments()->sum('amount');
                
                if ($bill->amount_paid >= $bill->total_amount) {
                    $bill->payment_status = 'paid';
                    $bill->status = 'paid';
                } elseif ($bill->amount_paid > 0) {
                    $bill->payment_status = 'partial';
                }
                
                $bill->save();
            }
        });

        static::deleted(function ($payment) {
            // Recalculate bill's amount paid
            if ($payment->bill) {
                $bill = $payment->bill;
                $bill->amount_paid = $bill->payments()->sum('amount');
                
                if ($bill->amount_paid >= $bill->total_amount) {
                    $bill->payment_status = 'paid';
                    $bill->status = 'paid';
                } elseif ($bill->amount_paid > 0) {
                    $bill->payment_status = 'partial';
                } else {
                    $bill->payment_status = 'unpaid';
                }
                
                $bill->save();
            }
        });
    }
}
