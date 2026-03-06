<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BankConnection extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'bank_id',
        'institution_name',
        'credentials',
        'plaid_access_token',
        'plaid_item_id',
        'plaid_institution_id',
        'plaid_cursor',
        'revolut_access_token',
        'revolut_refresh_token',
        'revolut_token_expires_at',
        'wise_access_token',
        'wise_refresh_token',
        'wise_token_expires_at',
        'status',
        'last_synced_at',
    ];

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