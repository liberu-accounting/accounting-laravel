<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\IsTenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankFeedTransaction extends Model
{
    use HasFactory;
    use IsTenantModel;

    #[\Override]
    protected $fillable = [
        'transaction_id',
        'bank_connection_id',
        'raw_data',
    ];

    #[\Override]
    protected $casts = [
        'raw_data' => 'json',
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
