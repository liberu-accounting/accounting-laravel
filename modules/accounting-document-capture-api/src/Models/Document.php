<?php

declare(strict_types=1);

namespace Liberu\Accounting\DocumentCaptureApi\DocumentCaptureApiServiceProvider\Models;

use Liberu\Accounting\DocumentCaptureApi\DocumentCaptureApiServiceProvider\Traits\IsTenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Document extends Model
{
    use HasFactory;
    use IsTenantModel;

    #[\Override]
    protected $fillable = ['documentable_type', 'documentable_id', 'name', 'disk', 'retention_until', 'team_id'];

    #[\Override]
    protected $casts = ['retention_until' => 'date'];

    #[\Override]
    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (Document $document): void {
            if (empty($document->team_id)) {
                $document->team_id = $document->documentable->team_id ?? auth()->user()?->currentTeam?->getKey();
            }
        });
    }

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return HasMany<DocumentVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class);
    }

    public function currentVersion(): HasOne
    {
        return $this->hasOne(DocumentVersion::class)->latestOfMany('version_number');
    }
}
