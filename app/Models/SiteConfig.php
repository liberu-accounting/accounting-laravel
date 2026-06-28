<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\IsTenantModel;
use Illuminate\Database\Eloquent\Model;

class SiteConfig extends Model
{
    use IsTenantModel;
    // Define table, fillable fields, etc. as needed
}
