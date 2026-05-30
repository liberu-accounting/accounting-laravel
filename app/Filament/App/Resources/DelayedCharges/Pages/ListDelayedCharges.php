<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\DelayedCharges\Pages;

use App\Filament\App\Resources\DelayedCharges\DelayedChargeResource;
use Filament\Resources\Pages\ListRecords;

class ListDelayedCharges extends ListRecords
{
    #[\Override]
    protected static string $resource = DelayedChargeResource::class;
}
