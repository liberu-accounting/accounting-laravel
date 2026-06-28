<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\IsTenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A FIFO/LIFO cost layer: a batch of stock purchased at a given unit cost.
 * The valuation service consumes these oldest- or newest-first on a sale.
 */
class InventoryCostLayer extends Model
{
    use HasFactory;
    use IsTenantModel;

    protected $fillable = [
        'inventory_item_id',
        'quantity',
        'unit_cost',
        'purchase_date',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_cost' => 'decimal:2',
        'purchase_date' => 'date',
    ];

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }
}
