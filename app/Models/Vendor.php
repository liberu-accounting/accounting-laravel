<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\IsTenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    use HasFactory;
    use IsTenantModel;

    #[\Override]
    protected $primaryKey = 'vendor_id';

    #[\Override]
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
        return $this->hasMany(VendorCredit::class, 'vendor_id', 'vendor_id');
    }
}
