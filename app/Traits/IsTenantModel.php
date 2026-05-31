<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\Team;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait IsTenantModel 
{

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}