<?php

namespace App\Models;

use App\Observers\TransactionObserver;
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
        'transaction_type',
    ];

    protected $casts = [
        'reconciled' => 'boolean',
        'exchange_rate' => 'float',
    ];

    protected static function boot()
    {
        parent::boot();
        static::observe(TransactionObserver::class);
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

    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }

    public function getAmountInDefaultCurrency()
    {
        $defaultCurrency = Currency::where('is_default', true)->first();
        if ($this->currency_id === $defaultCurrency->id) {
            return $this->amount;
        }
        return $this->amount * $this->exchange_rate;
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
