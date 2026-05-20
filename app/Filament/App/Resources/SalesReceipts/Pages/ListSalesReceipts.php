<?php

namespace App\Filament\App\Resources\SalesReceipts\Pages;

use App\Filament\App\Resources\SalesReceipts\SalesReceiptResource;
use Filament\Resources\Pages\ListRecords;

class ListSalesReceipts extends ListRecords
{
    protected static string $resource = SalesReceiptResource::class;
}
