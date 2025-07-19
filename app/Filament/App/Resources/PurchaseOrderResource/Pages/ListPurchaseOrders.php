<?php

namespace App\Filament\App\Resources\PurchaseOrderResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\App\Resources\PurchaseOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPurchaseOrders extends ListRecords
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
