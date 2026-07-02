<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Sales Receipt Item Model
 *
 * Represents a line item on a sales receipt
 *
 * @property int $item_id
 * @property int $sales_receipt_id
 * @property int|null $account_id
 * @property string $description
 * @property int $quantity
 * @property decimal $unit_price
 * @property decimal $amount
 */
class SalesReceiptItem extends Model
{
    use HasFactory;

    #[\Override]
    protected $primaryKey = 'item_id';

    #[\Override]
    public $timestamps = false;

    #[\Override]
    protected $fillable = [
        'sales_receipt_id',
        'account_id',
        'description',
        'quantity',
        'unit_price',
        'amount',
    ];

    #[\Override]
    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    #[\Override]
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item): void {
            // Auto-calculate amount if not provided
            if (! isset($item->amount) || $item->amount == 0) {
                $item->amount = $item->quantity * $item->unit_price;
            }
        });
    }

    /**
     * Get the sales receipt that owns this item
     */
    public function salesReceipt(): BelongsTo
    {
        return $this->belongsTo(SalesReceipt::class, 'sales_receipt_id', 'sales_receipt_id');
    }

    /**
     * Get the account for this item
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id', 'account_id');
    }
}
