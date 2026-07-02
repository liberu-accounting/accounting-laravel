<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\IsTenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiteSettings extends Model
{
    use HasFactory;
    use IsTenantModel;

    // ponytail: real table is `settings` (see create_site_settings_table migration);
    // Eloquent would otherwise pluralize to the non-existent `site_settings`.
    #[\Override]
    protected $table = 'settings';

    #[\Override]
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
        'youtube',
    ];
}
