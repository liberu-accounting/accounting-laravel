<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApprovalRule extends Model
{
    #[\Override]
    protected $fillable = ['team_id', 'approvable_type', 'min_amount', 'steps', 'deadline_days', 'fallback_role', 'is_active'];

    #[\Override]
    protected $casts = ['steps' => 'array', 'min_amount' => 'decimal:2', 'deadline_days' => 'integer', 'is_active' => 'boolean'];

    public static function matchFor(string $type, float $amount, int $teamId): ?self
    {
        return self::query()
            ->where('team_id', $teamId)
            ->where('approvable_type', $type)
            ->where('is_active', true)
            ->where('min_amount', '<=', $amount)
            ->orderByDesc('min_amount')
            ->first();
    }
}
