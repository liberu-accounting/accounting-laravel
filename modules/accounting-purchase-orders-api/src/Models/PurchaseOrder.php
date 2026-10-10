<?php

declare(strict_types=1);

namespace Liberu\Accounting\PurchaseOrdersApi\PurchaseOrdersApiServiceProvider\Models;

use Liberu\Accounting\PurchaseOrdersApi\PurchaseOrdersApiServiceProvider\Traits\IsTenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use HasFactory;
    use IsTenantModel;

    #[\Override]
    protected $primaryKey = 'purchase_order_id';

    #[\Override]
    protected $fillable = [
        'supplier_id',
        'po_number',
        'order_date',
        'expected_delivery_date',
        'total_amount',
        'status',
        'notes',
        'purchase_request_id',
        'team_id',
    ];

    #[\Override]
    protected $casts = [
        'order_date' => 'date',
        'expected_delivery_date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function purchaseRequest()
    {
        return $this->belongsTo(PurchaseRequest::class, 'purchase_request_id');
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class, 'purchase_order_id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'purchase_order_id');
    }

    public function bills()
    {
        return $this->hasMany(Bill::class, 'purchase_order_id', 'purchase_order_id');
    }

    public static function generatePoNumber(): string
    {
        $prefix = 'PO';
        $year = date('Y');
        $lastPo = self::whereYear('created_at', $year)
            ->orderBy('po_number', 'desc')
            ->first();

        $number = $lastPo ? (int) substr((string) $lastPo->po_number, -4) + 1 : 1;

        return $prefix.$year.str_pad((string) $number, 4, '0', STR_PAD_LEFT);
    }
}
