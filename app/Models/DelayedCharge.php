<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Delayed Charge Model
 * 
 * Records future charges to be added to a customer's invoice later.
 * Delayed charges don't affect accounts receivable or income until converted to an invoice.
 * Useful for tracking billable work that will be invoiced in the future.
 * 
 * @property int $delayed_charge_id
 * @property int $customer_id
 * @property string $charge_date
 * @property string $description
 * @property int $quantity
 * @property decimal $unit_price
 * @property decimal $amount
 * @property int|null $invoice_id
 * @property string $status
 */
class DelayedCharge extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'delayed_charge_id';

    protected $fillable = [
        'customer_id',
        'charge_date',
        'description',
        'quantity',
        'unit_price',
        'amount',
        'account_id',
        'invoice_id',
        'notes',
        'status',
    ];

    protected $casts = [
        'charge_date' => 'date',
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    protected $attributes = [
        'status' => 'pending',
        'quantity' => 1,
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($charge) {
            // Auto-calculate amount
            if (!isset($charge->amount) || $charge->amount == 0) {
                $charge->amount = $charge->quantity * $charge->unit_price;
            }
        });

        static::created(function ($charge) {
            if (empty($charge->status)) {
                $charge->status = 'pending';
                $charge->saveQuietly();
            }
        });
    }

    /**
     * Get the customer that owns the delayed charge
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
    }

    /**
     * Get the account for this charge
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id', 'account_id');
    }

    /**
     * Get the invoice this charge was added to (if any)
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id', 'invoice_id');
    }

    /**
     * Add this delayed charge to an invoice
     */
    public function addToInvoice(int $invoiceId): void
    {
        if ($this->status === 'invoiced') {
            throw new \Exception('Delayed charge has already been invoiced');
        }

        $this->invoice_id = $invoiceId;
        $this->status = 'invoiced';
        $this->save();
    }

    /**
     * Check if charge is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if charge has been invoiced
     */
    public function isInvoiced(): bool
    {
        return $this->status === 'invoiced';
    }

    /**
     * Void the delayed charge
     */
    public function void(): void
    {
        $this->status = 'void';
        $this->save();
    }
}
