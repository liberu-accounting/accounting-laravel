<?php

namespace App\Models;

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
        'debit_account_id',
        'credit_account_id',
    ];

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
}
