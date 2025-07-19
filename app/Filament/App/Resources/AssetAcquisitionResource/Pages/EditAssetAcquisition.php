<?php

namespace App\Filament\App\Resources\AssetAcquisitionResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\App\Resources\AssetAcquisitionResource;
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
