<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Estimates\Pages;

use App\Filament\App\Resources\Estimates\EstimateResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEstimate extends CreateRecord
{
    #[\Override]
    protected static string $resource = EstimateResource::class;
}
