<?php

namespace App\Filament\App\Resources\Estimates\Pages;

use App\Filament\App\Resources\Estimates\EstimateResource;
use Filament\Resources\Pages\EditRecord;

class EditEstimate extends EditRecord
{
    protected static string $resource = EstimateResource::class;
}
