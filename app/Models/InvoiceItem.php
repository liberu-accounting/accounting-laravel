<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\IsTenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A line item on a customer invoice. Mirrors BillItem.
 */
class InvoiceItem extends Model
{
    use HasFactory;
    use IsTenantModel;

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
        return $this->belongsTo(TaxRate::class);
    }

    public function calculateAmount(): float
    {
        $this->amount = (float) $this->quantity * (float) $this->unit_price;

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
