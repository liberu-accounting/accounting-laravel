<?php

namespace App\Filament\App\Resources\PurchaseOrders\Pages;

use App\Filament\App\Resources\PurchaseOrders\PurchaseOrderResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePurchaseOrder extends CreateRecord
{
    protected static string $resource = PurchaseOrderResource::class;
}
