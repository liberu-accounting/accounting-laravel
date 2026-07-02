<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\IsTenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReconciliationRule extends Model
{
    use HasFactory;
    use IsTenantModel;

    #[\Override]
    protected $fillable = [
        'team_id',
        'name',
        'match_field',
        'match_operator',
        'match_value',
        'match_value_secondary',
        'action_account_id',
        'priority',
        'is_active',
    ];

    #[\Override]
    protected $casts = [
        'priority' => 'integer',
        'is_active' => 'boolean',
    ];

    public function actionAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'action_account_id');
    }
}
