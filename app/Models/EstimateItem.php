<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstimateItem extends Model
{
    use HasFactory;

    #[\Override]
    protected $primaryKey = 'item_id';

    #[\Override]
    protected $fillable = [
        'estimate_id',
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
    public function estimate()
    {
        return $this->belongsTo(Estimate::class, 'estimate_id', 'estimate_id');
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
            // Recalculate estimate totals
            if ($item->estimate) {
                $item->estimate->calculateTotals();
            }
        });

        static::deleted(function ($item): void {
            // Recalculate estimate totals
            if ($item->estimate) {
                $item->estimate->calculateTotals();
            }
        });
    }
}
