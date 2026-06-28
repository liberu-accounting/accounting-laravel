<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\AssetAcquisitions\Pages;

use App\Filament\App\Resources\AssetAcquisitions\AssetAcquisitionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAssetAcquisition extends CreateRecord
{
    #[\Override]
    protected static string $resource = AssetAcquisitionResource::class;
}
