<?php

namespace App\Filament\App\Resources\AssetResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\App\Resources\AssetResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAssets extends ListRecords
{
    protected static string $resource = AssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}