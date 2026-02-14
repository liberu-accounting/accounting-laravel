

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
        'status',
        'last_synced_at',
    ];

    protected $casts = [
        'credentials' => 'encrypted',
        'plaid_access_token' => 'encrypted',
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
}