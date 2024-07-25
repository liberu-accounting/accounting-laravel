<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $primaryKey = 'transaction_id';

    protected $fillable = [
        'transaction_date',
        'transaction_description',
        'amount',
        'currency_id',
        'debit_account_id',
        'credit_account_id',
        'reconciled',
        'discrepancy_notes',
        'exchange_rate',
    ];

    protected $casts = [
        'reconciled' => 'boolean',
        'exchange_rate' => 'float',
    ];

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function debitAccount()
    {
        return $this->belongsTo(Account::class, 'debit_account_id');
    }

    public function creditAccount()
    {
        return $this->belongsTo(Account::class, 'credit_account_id');
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }

    public function getAmountInDefaultCurrency()
    {
        $defaultCurrency = Currency::where('is_default', true)->first();
        if ($this->currency_id === $defaultCurrency->id) {
            return $this->amount;
        }
        return $this->amount * $this->exchange_rate;
    }
}
