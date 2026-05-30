<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\PurchaseOrders\Pages;

use App\Filament\App\Resources\PurchaseOrders\PurchaseOrderResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePurchaseOrder extends CreateRecord
{
    #[\Override]
    protected static string $resource = PurchaseOrderResource::class;
}
