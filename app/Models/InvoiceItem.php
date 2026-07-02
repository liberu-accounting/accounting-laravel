<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A line item on a customer invoice. Mirrors BillItem.
 */
class InvoiceItem extends Model
{
    use HasFactory;

    protected $primaryKey = 'item_id';

    protected $fillable = [
        'invoice_id',
        'account_id',
        'description',
        'quantity',
        'unit_price',
        'amount',
        'tax_amount',
        'tax_rate_id',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function taxRate(): BelongsTo
    {
        // Explicit keys: TaxRate's PK is tax_rate_id, so Laravel's guessed FK
        // (tax_rate_tax_rate_id) is wrong and would always resolve to null.
        return $this->belongsTo(TaxRate::class, 'tax_rate_id', 'tax_rate_id');
    }

    public function calculateAmount(): float
    {
        $this->amount = (float) $this->quantity * (float) $this->unit_price;

        if ($this->taxRate) {
            $this->tax_amount = $this->taxRate->calculateTax($this->amount);
        }

        return (float) $this->amount;
    }

    #[\Override]
    protected static function boot()
    {
        parent::boot();

        static::saving(function (InvoiceItem $item): void {
            if ($item->isDirty(['quantity', 'unit_price'])) {
                $item->calculateAmount();
            }
        });

        static::saved(function (InvoiceItem $item): void {
            $item->invoice?->calculateTotals();
        });

        static::deleted(function (InvoiceItem $item): void {
            $item->invoice?->calculateTotals();
        });
    }
}
