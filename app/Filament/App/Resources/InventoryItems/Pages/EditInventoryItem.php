<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\InventoryItems\Pages;

use App\Filament\App\Resources\InventoryItems\InventoryItemResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditInventoryItem extends EditRecord
{
    #[\Override]
    protected static string $resource = InventoryItemResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
