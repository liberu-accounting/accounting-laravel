<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    use HasFactory;

    protected $primaryKey = 'inventory_item_id';

    protected $fillable = [
        'name',
        'sku',
        'description', 
        'unit_price',
        'current_quantity',
        'reorder_point',
        'account_id',
        'category_id',
        'is_active'
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'current_quantity' => 'integer',
        'reorder_point' => 'integer',
        'is_active' => 'boolean'
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function category() 
    {
        return $this->belongsTo(Category::class);
    }

    public function transactions()
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    public function adjustments()
    {
        return $this->hasMany(InventoryAdjustment::class);
    }

    public function updateQuantity($change)
    {
        $this->current_quantity += $change;
        $this->save();
    }

    public function needsReorder()
    {
        return $this->current_quantity <= $this->reorder_point;
    }
}