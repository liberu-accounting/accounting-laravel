<?php

namespace App\Filament\App\Resources\Estimates\Pages;

use App\Filament\App\Resources\Estimates\EstimateResource;
use Filament\Resources\Pages\ListRecords;

class ListEstimates extends ListRecords
{
    protected static string $resource = EstimateResource::class;
}
