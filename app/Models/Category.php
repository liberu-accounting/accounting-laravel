<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $primaryKey='category_id';
    
    protected $fillable=[
        'name',
    ];

    public function accounts()
    {
        return $this->belongsToMany(Account::class);
    }

    public function transactions()
    {
        return $this->belongsToMany(Transaction::class);
    }

}
