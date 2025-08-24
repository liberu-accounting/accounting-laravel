<?php

namespace App\Filament\App\Resources\PurchaseOrderResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\App\Resources\PurchaseOrderResource;
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
