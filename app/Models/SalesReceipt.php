<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Sales Receipt Model
 * 
 * Represents immediate payment transactions where the customer pays at the time of sale.
 * Unlike invoices, sales receipts record both the sale and payment simultaneously.
 * 
 * @property int $sales_receipt_id
 * @property int $customer_id
 * @property string $sales_receipt_number
 * @property string $sales_receipt_date
 * @property int|null $tax_rate_id
 * @property string $payment_method
 * @property string|null $reference_number
 * @property decimal $subtotal_amount
 * @property decimal $tax_amount
 * @property decimal $total_amount
 * @property string|null $notes
 * @property string $status
 */
class SalesReceipt extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'sales_receipt_id';

    protected $fillable = [
        'customer_id',
        'sales_receipt_number',
        'sales_receipt_date',
        'tax_rate_id',
        'payment_method',
        'reference_number',
        'subtotal_amount',
        'tax_amount',
        'total_amount',
        'notes',
        'status',
        'deposit_to_account_id',
    ];

    protected $casts = [
        'sales_receipt_date' => 'date',
        'subtotal_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($salesReceipt) {
            if (empty($salesReceipt->sales_receipt_number)) {
                $salesReceipt->sales_receipt_number = self::generateReceiptNumber();
            }
            if (empty($salesReceipt->status)) {
                $salesReceipt->status = 'completed';
            }
        });
    }

    /**
     * Generate unique sales receipt number
     */
    private static function generateReceiptNumber(): string
    {
        $lastReceipt = self::orderBy('sales_receipt_id', 'desc')->first();
        $nextNumber = $lastReceipt ? ((int) substr($lastReceipt->sales_receipt_number, 3)) + 1 : 1;
        return 'SR-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Get the customer that owns the sales receipt
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
    }

    /**
     * Get the tax rate for the sales receipt
     */
    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class, 'tax_rate_id', 'tax_rate_id');
    }

    /**
     * Get the deposit account for the sales receipt
     */
    public function depositAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'deposit_to_account_id', 'account_id');
    }

    /**
     * Get the line items for the sales receipt
     */
    public function items(): HasMany
    {
        return $this->hasMany(SalesReceiptItem::class, 'sales_receipt_id', 'sales_receipt_id');
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
        $this->save();
    }

    /**
     * Void the sales receipt
     */
    public function void(): void
    {
        $this->status = 'void';
        $this->save();
    }

    /**
     * Check if the sales receipt is void
     */
    public function isVoid(): bool
    {
        return $this->status === 'void';
    }

    /**
     * Create a refund receipt from this sales receipt
     */
    public function createRefund(array $items, string $reason = null): RefundReceipt
    {
        $refund = RefundReceipt::create([
            'customer_id' => $this->customer_id,
            'sales_receipt_id' => $this->sales_receipt_id,
            'refund_date' => now(),
            'reason' => $reason,
            'payment_method' => $this->payment_method,
            'status' => 'draft',
        ]);

        foreach ($items as $item) {
            $refund->items()->create($item);
        }

        $refund->calculateTotals();

        return $refund;
    }
}
