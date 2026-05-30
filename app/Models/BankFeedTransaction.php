<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\IsTenantModel;

class BankFeedTransaction extends Model
{
    use HasFactory;
    use IsTenantModel;

    #[\Override]
    protected $fillable = [
        'transaction_id',
        'bank_connection_id',
        'raw_data'
    ];

    #[\Override]
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
