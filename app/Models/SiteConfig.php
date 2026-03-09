<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\IsTenantModel;

class SiteConfig extends Model
{
    use IsTenantModel;
    // Define table, fillable fields, etc. as needed
}