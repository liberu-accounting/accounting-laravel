<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\ExchangeRateService;

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
        'transaction_type',
    ];

    protected $casts = [
        'reconciled' => 'boolean',
        'exchange_rate' => 'decimal:6',
        'amount' => 'decimal:2',
        'transaction_date' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($transaction) {
            if (!$transaction->exchange_rate) {
                $defaultCurrency = Currency::where('is_default', true)->first();
                if ($transaction->currency_id !== $defaultCurrency->currency_id) {
                    $exchangeRateService = app(ExchangeRateService::class);
                    $transaction->exchange_rate = $exchangeRateService->getExchangeRate(
                        $transaction->currency,
                        $defaultCurrency
                    );
                } else {
                    $transaction->exchange_rate = 1;
                }
            }
        });
    }

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

    public function inventoryTransactions()
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    public function getAmountInCurrency(Currency $targetCurrency)
    {
        if ($this->currency_id === $targetCurrency->currency_id) {
            return $this->amount;
        }

        $exchangeRateService = app(ExchangeRateService::class);
        $rate = $exchangeRateService->getExchangeRate($this->currency, $targetCurrency);
        
        return $this->amount * $rate;
    }

    public function getAmountInDefaultCurrency()
    {
        $defaultCurrency = Currency::where('is_default', true)->first();
        return $this->getAmountInCurrency($defaultCurrency);
    }

    public function updateInventory()
    {
        foreach ($this->inventoryTransactions as $inventoryTx) {
            $quantity = $inventoryTx->quantity;
            if ($this->transaction_type === 'sale') {
                $quantity *= -1;
            }
            $inventoryTx->inventoryItem->updateQuantity($quantity);
        }
    }
}
