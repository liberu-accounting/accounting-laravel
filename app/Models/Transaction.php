<?php

namespace App\Models;

use App\Observers\TransactionObserver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\ExchangeRateService;
use App\Traits\IsTenantModel;

class Transaction extends Model
{
    use HasFactory;
    use IsTenantModel;

    protected $primaryKey = 'transaction_id';

    protected $fillable = [
        'transaction_date',
        'transaction_description',
        'description',
        'amount',
        'currency_id',
        'account_id',
        'debit_account_id',
        'credit_account_id',
        'bank_statement_id',
        'reconciled',
        'discrepancy_notes',
        'reconciled_at',
        'reconciled_by_user_id',
        'exchange_rate',
        'transaction_type',
        'type',
        'external_id',
        'bank_connection_id',
        'category',
        'status',
        'user_id',
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

        static::observe(TransactionObserver::class);

        
        static::creating(function ($transaction) {
            if (!$transaction->exchange_rate) {
                $defaultCurrency = Currency::where('is_default', true)->first();
                if ($defaultCurrency && $transaction->currency_id && $transaction->currency_id !== $defaultCurrency->currency_id) {
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

    public function bankConnection()
    {
        return $this->belongsTo(BankConnection::class);
    }

    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'auditable');
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
