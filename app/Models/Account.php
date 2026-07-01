<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\ExchangeRateService;
use App\Traits\IsTenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;
    use IsTenantModel;

    #[\Override]
    protected $fillable = [
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
        'allow_manual_entry',
    ];

    #[\Override]
    protected $casts = [
        'balance' => 'decimal:2',
        'opening_balance' => 'decimal:2',
        'is_active' => 'boolean',
        'allow_manual_entry' => 'boolean',
    ];

    #[\Override]
    protected static function boot()
    {
        parent::boot();

        static::creating(function (Account $account): void {
            if (empty($account->user_id) && auth()->check()) {
                $account->user_id = auth()->id();
            }

            // ponytail: stamp tenant team on non-Filament creates (Filament stamps panel creates itself); additive, leaves DB default when tenantless.
            if (empty($account->team_id) && ($team = auth()->user()?->currentTeam) !== null) {
                $account->team_id = $team->getKey();
            }

            // Set normal_balance based on account_type if not provided
            if (! $account->normal_balance) {
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
        // An account with no explicit currency holds its balance in the default
        // (reporting) currency — fall back to it rather than passing null.
        $source = $this->currency ?? Currency::where('is_default', true)->first();

        if (! $source || $source->currency_id === $targetCurrency->currency_id) {
            return $this->balance;
        }

        $rate = app(ExchangeRateService::class)->getExchangeRate($source, $targetCurrency);

        return $this->balance * ($rate ?? 1);
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
        if (! $this->allow_manual_entry) {
            return false;
        }

        // Parent accounts with children should not accept direct entries
        if ($this->children()->count() > 0) {
            return false;
        }

        return $this->is_active;
    }
}
