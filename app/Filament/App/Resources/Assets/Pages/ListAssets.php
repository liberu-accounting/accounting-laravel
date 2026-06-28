<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Assets\Pages;

use App\Filament\App\Resources\Assets\AssetResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAssets extends ListRecords
{
    #[\Override]
    protected static string $resource = AssetResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
