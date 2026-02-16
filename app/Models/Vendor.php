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

    public function bills()
    {
        return $this->hasMany(Bill::class, 'vendor_id', 'vendor_id');
    }

    public function vendorCredits()
    {
        return $this->hasMany(VendorCredit::class, 'vendor_id', 'id');
    }
}
