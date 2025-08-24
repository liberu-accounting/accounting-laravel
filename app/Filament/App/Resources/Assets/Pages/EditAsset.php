<?php

namespace App\Filament\App\Resources\AssetResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\App\Resources\AssetResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAsset extends EditRecord
{
    protected static string $resource = AssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
