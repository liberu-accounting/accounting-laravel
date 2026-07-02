<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillItem extends Model
{
    use HasFactory;

    #[\Override]
    protected $primaryKey = 'item_id';

    #[\Override]
    protected $fillable = [
        'bill_id',
        'account_id',
        'description',
        'quantity',
        'unit_price',
        'amount',
        'tax_amount',
        'tax_rate_id',
    ];

    #[\Override]
    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
    ];

    // Relationships
    public function bill()
    {
        return $this->belongsTo(Bill::class, 'bill_id', 'bill_id');
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function taxRate()
    {
        // Explicit keys: TaxRate's PK is tax_rate_id, so Laravel's guessed FK
        // (tax_rate_tax_rate_id) is wrong and would always resolve to null.
        return $this->belongsTo(TaxRate::class, 'tax_rate_id', 'tax_rate_id');
    }

    // Business Logic
    public function calculateAmount(): int|float
    {
        $this->amount = $this->quantity * $this->unit_price;

        if ($this->taxRate) {
            $this->tax_amount = $this->taxRate->calculateTax($this->amount);
        }

        return (float) $this->amount;
    }

    // Auto-calculate amount when saving
    #[\Override]
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item): void {
            if ($item->isDirty(['quantity', 'unit_price'])) {
                $item->calculateAmount();
            }
        });

        static::saved(function ($item): void {
            // Recalculate bill totals
            if ($item->bill) {
                $item->bill->calculateTotals();
            }
        });

        static::deleted(function ($item): void {
            // Recalculate bill totals
            if ($item->bill) {
                $item->bill->calculateTotals();
            }
        });
    }
}
