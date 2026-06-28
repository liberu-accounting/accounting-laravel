<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\IsTenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryTransaction extends Model
{
    use HasFactory;
    use IsTenantModel;

    #[\Override]
    protected $primaryKey = 'inventory_transaction_id';

    #[\Override]
    protected $fillable = [
        'inventory_item_id',
        'transaction_id',
        'quantity',
        'unit_price',
        'transaction_type',
        'notes'
    ];

    #[\Override]
    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2'
    ];

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
}
