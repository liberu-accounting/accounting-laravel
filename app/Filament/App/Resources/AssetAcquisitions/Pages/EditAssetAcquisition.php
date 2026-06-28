<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\AssetAcquisitions\Pages;

use App\Filament\App\Resources\AssetAcquisitions\AssetAcquisitionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAssetAcquisition extends EditRecord
{
    #[\Override]
    protected static string $resource = AssetAcquisitionResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
