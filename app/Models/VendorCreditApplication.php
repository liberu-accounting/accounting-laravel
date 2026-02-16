<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Vendor Credit Application Model
 * 
 * Tracks the application of vendor credits to bills
 * 
 * @property int $application_id
 * @property int $vendor_credit_id
 * @property int $bill_id
 * @property decimal $amount_applied
 * @property date $application_date
 */
class VendorCreditApplication extends Model
{
    use HasFactory;

    protected $primaryKey = 'application_id';

    protected $fillable = [
        'vendor_credit_id',
        'bill_id',
        'amount_applied',
        'application_date',
    ];

    protected $casts = [
        'amount_applied' => 'decimal:2',
        'application_date' => 'date',
    ];

    /**
     * Get the vendor credit
     */
    public function vendorCredit(): BelongsTo
    {
        return $this->belongsTo(VendorCredit::class, 'vendor_credit_id', 'vendor_credit_id');
    }

    /**
     * Get the bill
     */
    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class, 'bill_id', 'bill_id');
    }
}
