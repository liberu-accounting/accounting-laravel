<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Database\Eloquent\Model;

final readonly class ApprovableRejected
{
    public function __construct(
        public Model $approvable,
        public ?string $reason = null,
    ) {}
}
