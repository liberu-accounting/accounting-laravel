<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Customer extends Model
{
    use HasFactory, Notifiable;

    protected $primaryKey = 'customer_id';

    protected $fillable = [
        'customer_name',
        'customer_last_name',
        'customer_address',
        'customer_email',
        'customer_phone',
        'customer_city',
        'credit_limit',
        'current_balance',
        'credit_hold'
    ];

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'customer_id');
    }

    public function isOverCreditLimit(): bool
    {
        return $this->credit_limit > 0 && $this->current_balance >= $this->credit_limit;
    }

    public function updateBalance()
    {
        $this->current_balance = $this->invoices()
            ->where('payment_status', 'pending')
            ->sum('total_amount');
        $this->save();
    }
}
