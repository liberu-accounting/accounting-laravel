<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    use HasFactory;

    #[\Override]
    protected $primaryKey = 'item_id';

    #[\Override]
    protected $fillable = [
        'purchase_order_id',
        'description',
        'quantity',
        'unit_price',
        'total_price',
    ];

    #[\Override]
    protected $casts = [
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }
}
