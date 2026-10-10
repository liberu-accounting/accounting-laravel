<?php

// src/app/Models/SalesOrder.php
declare(strict_types=1);

namespace Liberu\Accounting\SalesOrdersApi\SalesOrdersApiServiceProvider\Models;

use Liberu\Accounting\SalesOrdersApi\SalesOrdersApiServiceProvider\Traits\IsTenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SalesOrder extends Model
{
    use HasFactory;
    use IsTenantModel;

    #[\Override]
    protected $fillable = [
        'customer_id', 'estimate_id', 'sales_order_number', 'order_date',
        'subtotal_amount', 'tax_amount', 'total_amount', 'status', 'notes', 'team_id',
    ];

    #[\Override]
    protected $casts = [
        'order_date' => 'date',
        'subtotal_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    #[\Override]
    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (SalesOrder $order): void {
            if (empty($order->team_id) && ($team = auth()->user()?->currentTeam) !== null) {
                $order->team_id = $team->getKey();
            }
            if (empty($order->sales_order_number)) {
                $order->sales_order_number = 'SO-'.str_pad((string) ((int) static::max('id') + 1), 6, '0', STR_PAD_LEFT);
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function estimate(): BelongsTo
    {
        return $this->belongsTo(Estimate::class, 'estimate_id', 'estimate_id');
    }

    /**
     * @return HasMany<SalesOrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(SalesOrderItem::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class, 'sales_order_id');
    }

    public function confirm(): void
    {
        $this->update(['status' => 'confirmed']);
    }

    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }
}
