<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;

    protected $primaryKey = 'account_id';

    protected $fillable = [
        'user_id', 
        'account_number',
        'account_name',
        'account_type',
        'balance',
    ];

    public function user()
    {
         return $this->belongsTo(User::class);
    }

    public function transactions()
    {
         return $this->hasMany(Transaction::class);
    }

    public function categories()
    {
         return $this->belongsToMany(Category::class);
    }
}
