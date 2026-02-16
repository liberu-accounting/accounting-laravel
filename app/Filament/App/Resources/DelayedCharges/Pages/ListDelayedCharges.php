<?php

namespace App\Filament\App\Resources\DelayedCharges\Pages;

use App\Filament\App\Resources\DelayedCharges\DelayedChargeResource;
use Filament\Resources\Pages\ListRecords;

class ListDelayedCharges extends ListRecords
{
    protected static string $resource = DelayedChargeResource::class;
}
