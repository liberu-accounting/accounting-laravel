<?php

declare(strict_types=1);

namespace App\Modules\Events;

use App\Modules\Contracts\ModuleInterface;

final readonly class ModuleEnabled
{
    public function __construct(
        public ModuleInterface $module,
    ) {}
}
