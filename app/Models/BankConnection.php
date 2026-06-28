<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\IsTenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankConnection extends Model
{
    use HasFactory;
    use IsTenantModel;

    #[\Override]
    protected $fillable = [
        'bank_id',
        'institution_name',
        'plaid_item_id',
        'plaid_institution_id',
        'plaid_cursor',
        'revolut_token_expires_at',
        'wise_token_expires_at',
        'status',
        'last_synced_at',
    ];

    #[\Override]
    protected $casts = [
        'credentials' => 'encrypted:array',
        'plaid_access_token' => 'encrypted',
        'revolut_access_token' => 'encrypted',
        'revolut_refresh_token' => 'encrypted',
        'revolut_token_expires_at' => 'datetime',
        'wise_access_token' => 'encrypted',
        'wise_refresh_token' => 'encrypted',
        'wise_token_expires_at' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function ($bankConnection): void {
            if (empty($bankConnection->user_id) && auth()->check()) {
                $bankConnection->user_id = auth()->id();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function bankFeedTransactions()
    {
        return $this->hasMany(BankFeedTransaction::class);
    }

    public function balances()
    {
        return $this->hasMany(BankAccountBalance::class);
    }
}
