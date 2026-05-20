<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\IsTenantModel;

class SiteSettings extends Model
{
    use HasFactory;
    use IsTenantModel;

    protected $fillable = [
        'name',
        'currency',
        'default_language',
        'address',
        'country',
        'email',
        'phone_01',
        'phone_02',
        'phone_03',
        'facebook',
        'twitter',
        'github',
        'youtube'
    ];
}
