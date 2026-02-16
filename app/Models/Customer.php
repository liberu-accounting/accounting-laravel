<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Customer extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $primaryKey = 'customer_id';
    protected $guard = 'customer';

    protected $fillable = [
        'customer_name',
        'customer_last_name',
        'customer_address',
        'customer_email',
        'customer_phone',
        'customer_city',
        'credit_limit',
        'current_balance',
        'credit_hold',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'customer_id');
    }

    public function estimates()
    {
        return $this->hasMany(Estimate::class, 'customer_id', 'customer_id');
    }

    public function creditMemos()
    {
        return $this->hasMany(CreditMemo::class, 'customer_id', 'customer_id');
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