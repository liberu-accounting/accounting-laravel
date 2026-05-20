<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\IsTenantModel;

class Category extends Model
{
    use HasFactory;
    use IsTenantModel;

    protected $primaryKey='category_id';

    protected $fillable=[
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
