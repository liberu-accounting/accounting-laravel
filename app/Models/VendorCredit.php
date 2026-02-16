<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Vendor Credit Model
 * 
 * Represents credits received from vendors for returns, overpayments, or adjustments.
 * Vendor credits can be applied to future bills from the vendor.
 * 
 * @property int $vendor_credit_id
 * @property int $vendor_id
 * @property string $vendor_credit_number
 * @property string $credit_date
 * @property int|null $bill_id
 * @property decimal $subtotal_amount
 * @property decimal $tax_amount
 * @property decimal $total_amount
 * @property decimal $amount_applied
 * @property decimal $amount_remaining
 * @property string $reason
 * @property string $status
 */
class VendorCredit extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'vendor_credit_id';

    protected $fillable = [
        'vendor_id',
        'vendor_credit_number',
        'credit_date',
        'bill_id',
        'tax_rate_id',
        'subtotal_amount',
        'tax_amount',
        'total_amount',
        'amount_applied',
        'amount_remaining',
        'reason',
        'notes',
        'status',
    ];

    protected $casts = [
        'credit_date' => 'date',
        'subtotal_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'amount_applied' => 'decimal:2',
        'amount_remaining' => 'decimal:2',
    ];

    protected $attributes = [
        'amount_applied' => 0,
        'status' => 'open',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($credit) {
            if (empty($credit->vendor_credit_number)) {
                $credit->vendor_credit_number = self::generateCreditNumber();
            }
            if (empty($credit->status)) {
                $credit->status = 'open';
            }
        });

        static::saved(function ($credit) {
            // Update amount_remaining
            $credit->amount_remaining = $credit->total_amount - $credit->amount_applied;
            
            // Update status based on remaining amount
            if ($credit->amount_remaining <= 0) {
                $credit->status = 'applied';
            } elseif ($credit->amount_applied > 0) {
                $credit->status = 'partial';
            } else {
                $credit->status = 'open';
            }
            
            if ($credit->isDirty(['amount_remaining', 'status'])) {
                $credit->saveQuietly();
            }
        });
    }

    /**
     * Generate unique vendor credit number
     */
    private static function generateCreditNumber(): string
    {
        $lastCredit = self::orderBy('vendor_credit_id', 'desc')->first();
        $nextNumber = $lastCredit ? ((int) substr($lastCredit->vendor_credit_number, 3)) + 1 : 1;
        return 'VC-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Get the vendor that owns the credit
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id', 'id');
    }

    /**
     * Get the original bill this credit is for (if any)
     */
    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class, 'bill_id', 'bill_id');
    }

    /**
     * Get the tax rate for the credit
     */
    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class, 'tax_rate_id', 'tax_rate_id');
    }

    /**
     * Get the line items for the vendor credit
     */
    public function items(): HasMany
    {
        return $this->hasMany(VendorCreditItem::class, 'vendor_credit_id', 'vendor_credit_id');
    }

    /**
     * Get applications of this credit to bills
     */
    public function applications(): HasMany
    {
        return $this->hasMany(VendorCreditApplication::class, 'vendor_credit_id', 'vendor_credit_id');
    }

    /**
     * Calculate totals from line items
     */
    public function calculateTotals(): void
    {
        $subtotal = $this->items->sum('amount');
        $this->subtotal_amount = $subtotal;

        // Calculate tax
        if ($this->taxRate) {
            $this->tax_amount = $subtotal * ($this->taxRate->rate / 100);
        } else {
            $this->tax_amount = 0;
        }

        $this->total_amount = $this->subtotal_amount + $this->tax_amount;
        $this->amount_remaining = $this->total_amount - $this->amount_applied;
        $this->save();
    }

    /**
     * Apply credit to a bill
     */
    public function applyToBill(int $billId, float $amount): void
    {
        if ($amount > $this->amount_remaining) {
            throw new \Exception('Amount exceeds remaining credit balance');
        }

        $this->applications()->create([
            'bill_id' => $billId,
            'amount_applied' => $amount,
            'application_date' => now(),
        ]);

        $this->amount_applied += $amount;
        $this->save();
    }

    /**
     * Void the vendor credit
     */
    public function void(): void
    {
        $this->status = 'void';
        $this->save();
    }

    /**
     * Check if the credit is fully applied
     */
    public function isFullyApplied(): bool
    {
        return $this->amount_remaining <= 0;
    }
}
