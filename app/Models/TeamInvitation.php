<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Jetstream\Jetstream;
use Laravel\Jetstream\TeamInvitation as JetstreamTeamInvitation;
use App\Traits\IsTenantModel;

class TeamInvitation extends JetstreamTeamInvitation
{
    use IsTenantModel;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    #[\Override]
    protected $fillable = [
        'email',
        'role',
    ];

    /**
     * Get the team that the invitation belongs to.
     */
    #[\Override]
    public function team(): BelongsTo
    {
        return $this->belongsTo(Jetstream::teamModel());
    }
}
