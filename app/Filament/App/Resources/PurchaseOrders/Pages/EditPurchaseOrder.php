<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\PurchaseOrders\Pages;

use App\Filament\App\Resources\PurchaseOrders\PurchaseOrderResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPurchaseOrder extends EditRecord
{
    #[\Override]
    protected static string $resource = PurchaseOrderResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
