<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\ExchangeRateService;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'account_number',
        'account_name',
        'account_type',
        'normal_balance',
        'balance',
        'opening_balance',
        'description',
        'currency_id',
        'parent_id',
        'industry_type',
        'is_active',
        'allow_manual_entry'
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'opening_balance' => 'decimal:2',
        'is_active' => 'boolean',
        'allow_manual_entry' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        // Set normal_balance based on account_type if not provided
        static::creating(function ($account) {
            if (!$account->normal_balance) {
                $account->normal_balance = in_array($account->account_type, ['asset', 'expense']) 
                    ? 'debit' 
                    : 'credit';
            }
        });
    }

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

    public function journalEntryLines()
    {
        return $this->hasMany(JournalEntryLine::class);
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

    /**
     * Calculate the current balance including child accounts
     */
    public function getCalculatedBalanceAttribute()
    {
        $balance = $this->balance;
        
        foreach ($this->children as $child) {
            $balance += $child->calculated_balance;
        }
        
        return $balance;
    }

    /**
     * Check if this account can accept manual journal entries
     */
    public function canAcceptEntries()
    {
        if (!$this->allow_manual_entry) {
            return false;
        }

        // Parent accounts with children should not accept direct entries
        if ($this->children()->count() > 0) {
            return false;
        }

        return $this->is_active;
    }
}
