<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\IsTenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConnectedAccount extends Model
{
    use HasFactory, IsTenantModel;

    #[\Override]
    protected $fillable = [
        'user_id',
        'provider',
        'provider_id',
        'name',
        'nickname',
        'email',
        'avatar_path',
        'token',
        'secret',
        'refresh_token',
        'expires_at',
    ];

    #[\Override]
    protected $casts = [
        'created_at' => 'datetime',
        'expires_at' => 'datetime',
    ];
}
