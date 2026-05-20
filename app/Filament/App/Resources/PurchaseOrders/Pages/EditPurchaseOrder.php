<?php

namespace App\Filament\App\Resources\PurchaseOrders\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\App\Resources\PurchaseOrders\PurchaseOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPurchaseOrder extends EditRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
