<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Refund Receipt Model
 * 
 * Records refunds given to customers for returned goods or overpayments.
 * Decreases income and shows the outgoing payment to the customer.
 * 
 * @property int $refund_receipt_id
 * @property int $customer_id
 * @property int|null $sales_receipt_id
 * @property int|null $invoice_id
 * @property string $refund_receipt_number
 * @property string $refund_date
 * @property string $payment_method
 * @property string|null $reference_number
 * @property decimal $subtotal_amount
 * @property decimal $tax_amount
 * @property decimal $total_amount
 * @property string|null $reason
 * @property string $status
 */
class RefundReceipt extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'refund_receipt_id';

    protected $fillable = [
        'customer_id',
        'sales_receipt_id',
        'invoice_id',
        'refund_receipt_number',
        'refund_date',
        'payment_method',
        'reference_number',
        'subtotal_amount',
        'tax_amount',
        'total_amount',
        'reason',
        'notes',
        'status',
        'refund_from_account_id',
    ];

    protected $casts = [
        'refund_date' => 'date',
        'subtotal_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    protected $attributes = [
        'status' => 'draft',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($refund) {
            if (empty($refund->refund_receipt_number)) {
                $refund->refund_receipt_number = self::generateRefundNumber();
            }
        });
    }

    /**
     * Generate unique refund receipt number
     */
    private static function generateRefundNumber(): string
    {
        $lastRefund = self::orderBy('refund_receipt_id', 'desc')->first();
        $nextNumber = $lastRefund ? ((int) substr($lastRefund->refund_receipt_number, 3)) + 1 : 1;
        return 'RR-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Get the customer that owns the refund receipt
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
    }

    /**
     * Get the original sales receipt (if applicable)
     */
    public function salesReceipt(): BelongsTo
    {
        return $this->belongsTo(SalesReceipt::class, 'sales_receipt_id', 'sales_receipt_id');
    }

    /**
     * Get the original invoice (if applicable)
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id', 'invoice_id');
    }

    /**
     * Get the refund account
     */
    public function refundAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'refund_from_account_id', 'account_id');
    }

    /**
     * Get the line items for the refund receipt
     */
    public function items(): HasMany
    {
        return $this->hasMany(RefundReceiptItem::class, 'refund_receipt_id', 'refund_receipt_id');
    }

    /**
     * Calculate totals from line items
     */
    public function calculateTotals(): void
    {
        $subtotal = $this->items->sum('amount');
        $this->subtotal_amount = $subtotal;

        // Tax is typically the same ratio as original transaction
        $taxRate = 0;
        if ($this->salesReceipt && $this->salesReceipt->subtotal_amount > 0) {
            $taxRate = $this->salesReceipt->tax_amount / $this->salesReceipt->subtotal_amount;
        }

        $this->tax_amount = $subtotal * $taxRate;
        $this->total_amount = $this->subtotal_amount + $this->tax_amount;
        $this->save();
    }

    /**
     * Process (complete) the refund
     */
    public function process(): void
    {
        $this->status = 'completed';
        $this->save();
    }

    /**
     * Void the refund receipt
     */
    public function void(): void
    {
        $this->status = 'void';
        $this->save();
    }

    /**
     * Check if the refund is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}
