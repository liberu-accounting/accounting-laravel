<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BankAccountBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'bank_connection_id',
        'plaid_account_id',
        'account_name',
        'account_type',
        'account_subtype',
        'current_balance',
        'available_balance',
        'limit_amount',
        'iso_currency_code',
        'unofficial_currency_code',
        'last_updated_at',
    ];

    protected $casts = [
        'current_balance' => 'decimal:2',
        'available_balance' => 'decimal:2',
        'limit_amount' => 'decimal:2',
        'last_updated_at' => 'datetime',
    ];

    public function bankConnection()
    {
        return $this->belongsTo(BankConnection::class);
    }
}
