<?php

namespace App\Models;

use App\Traits\IsTenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankStatement extends Model
{
    use HasFactory;
    use IsTenantModel;

    protected $fillable = [
        'statement_date',
        'account_id',
        'total_credits',
        'total_debits',
        'ending_balance',
        'reconciled',
        'team_id',
    ];

    protected $casts = [
        'statement_date' => 'date',
        'total_credits' => 'decimal:2',
        'total_debits' => 'decimal:2',
        'ending_balance' => 'decimal:2',
        'reconciled' => 'boolean',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}