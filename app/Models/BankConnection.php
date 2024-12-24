

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BankConnection extends Model
{
    use HasFactory;

    protected $fillable = [
        'bank_id',
        'credentials',
        'status'
    ];

    protected $casts = [
        'credentials' => 'encrypted'
    ];

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function bankFeedTransactions()
    {
        return $this->hasMany(BankFeedTransaction::class);
    }
}