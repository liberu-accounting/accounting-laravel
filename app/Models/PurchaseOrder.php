<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $primaryKey = 'purchase_order_id';

    protected $fillable = [
        'supplier_id',
        'po_number',
        'order_date',
        'expected_delivery_date',
        'total_amount',
        'status',
        'notes'
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_delivery_date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class, 'purchase_order_id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'purchase_order_id');
    }

    public static function generatePoNumber()
    {
        $prefix = 'PO';
        $year = date('Y');
        $lastPo = self::whereYear('created_at', $year)
            ->orderBy('po_number', 'desc')
            ->first();

        if (!$lastPo) {
            $number = 1;
        } else {
            $number = (int)substr($lastPo->po_number, -4) + 1;
        }

        return $prefix . $year . str_pad($number, 4, '0', STR_PAD_LEFT);
    }
}
