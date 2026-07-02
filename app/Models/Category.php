<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    // ponytail: primaryKey defaults to `id` — the table is created with $table->id()
    // and every external FK (inventory_items, expenses) constrains against categories.id,
    // so the old `category_id` override was fictional and broke parent()/children().

    #[\Override]
    protected $fillable = [
        'name',
        'parent_id',
    ];

    public function accounts()
    {
        return $this->belongsToMany(Account::class);
    }

    public function transactions()
    {
        return $this->belongsToMany(Transaction::class);
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }
}
