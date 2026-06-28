<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\InventoryItems\Pages;

use App\Filament\App\Resources\InventoryItems\InventoryItemResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInventoryItem extends CreateRecord
{
    #[\Override]
    protected static string $resource = InventoryItemResource::class;
}
