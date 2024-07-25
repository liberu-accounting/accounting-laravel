<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;

    protected $primaryKey = 'account_id';

    protected $fillable = [
        'user_id',
        'account_number',
        'account_name',
        'account_type',
        'balance',
        'currency_id',
    ];

    public function user()
    {
         return $this->belongsTo(User::class);
    }

    public function currency()
    {
         return $this->belongsTo(Currency::class);
    }

    public function transactions()
    {
         return $this->hasMany(Transaction::class);
    }

    public function categories()
    {
         return $this->belongsToMany(Category::class);
    }

    public function getBalanceInDefaultCurrency()
    {
        $defaultCurrency = Currency::where('is_default', true)->first();
        if ($this->currency_id === $defaultCurrency->id) {
            return $this->balance;
        }
        $exchangeRate = ExchangeRate::where('from_currency_id', $this->currency_id)
            ->where('to_currency_id', $defaultCurrency->id)
            ->latest('date')
            ->first();
        return $this->balance * $exchangeRate->rate;
    }
}
