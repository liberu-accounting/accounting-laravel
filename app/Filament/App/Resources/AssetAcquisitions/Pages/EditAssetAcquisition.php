<?php

namespace App\Filament\App\Resources\AssetAcquisitions\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\App\Resources\AssetAcquisitions\AssetAcquisitionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAssetAcquisition extends EditRecord
{
    protected static string $resource = AssetAcquisitionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
