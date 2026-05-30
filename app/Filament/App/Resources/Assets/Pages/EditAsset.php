<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Assets\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\App\Resources\Assets\AssetResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAsset extends EditRecord
{
    #[\Override]
    protected static string $resource = AssetResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
