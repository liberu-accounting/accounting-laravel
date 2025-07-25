<?php

namespace App\Filament\App\Resources\AssetAcquisitionResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\App\Resources\AssetAcquisitionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAssetAcquisitions extends ListRecords
{
    protected static string $resource = AssetAcquisitionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
