<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\DelayedCharges\Pages;

use App\Filament\App\Resources\DelayedCharges\DelayedChargeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDelayedCharge extends CreateRecord
{
    #[\Override]
    protected static string $resource = DelayedChargeResource::class;
}
