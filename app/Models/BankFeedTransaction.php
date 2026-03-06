<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BankFeedTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'bank_connection_id',
        'raw_data'
    ];

    protected $casts = [
        'raw_data' => 'json'
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function bankConnection()
    {
        return $this->belongsTo(BankConnection::class);
    }
}