<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\ExchangeRateService;

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
        'parent_id',
        'industry_type'
    ];

    protected $casts = [
        'balance' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function parent()
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Account::class, 'parent_id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }

    public function getBalanceInCurrency(Currency $targetCurrency)
    {
        if ($this->currency_id === $targetCurrency->currency_id) {
            return $this->balance;
        }

        $exchangeRateService = app(ExchangeRateService::class);
        $rate = $exchangeRateService->getExchangeRate($this->currency, $targetCurrency);
        
        return $this->balance * $rate;
    }

    public function getBalanceInDefaultCurrency()
    {
        $defaultCurrency = Currency::where('is_default', true)->first();
        return $this->getBalanceInCurrency($defaultCurrency);
    }
}
