<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'tax_id',
        'payment_terms',
        'status'
    ];

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
}
