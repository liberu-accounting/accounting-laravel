<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    use HasFactory;

    #[\Override]
    protected $primaryKey = 'currency_id';

    #[\Override]
    protected $fillable = [
        'code',
        'name',
        'symbol',
        'is_default',
    ];

    #[\Override]
    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function accounts()
    {
        return $this->hasMany(Account::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
