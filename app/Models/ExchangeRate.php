<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    use HasFactory;

    protected $primaryKey = 'exchange_rate_id';

    protected $fillable = [
        'from_currency_id',
        'to_currency_id',
        'rate',
        'date',
    ];

    protected $casts = [
        'rate' => 'float',
        'date' => 'date',
    ];

    public function fromCurrency()
    {
        return $this->belongsTo(Currency::class, 'from_currency_id');
    }

    public function toCurrency()
    {
        return $this->belongsTo(Currency::class, 'to_currency_id');
    }
}