<?php

namespace App\Filament\Admin\Resources\AssetAcquisitionResource\Pages;

use App\Filament\Admin\Resources\AssetAcquisitionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAssetAcquisition extends EditRecord
{
    protected static string $resource = AssetAcquisitionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
