<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\PurchaseOrders\Pages;

use App\Filament\App\Resources\PurchaseOrders\PurchaseOrderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPurchaseOrders extends ListRecords
{
    #[\Override]
    protected static string $resource = PurchaseOrderResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
