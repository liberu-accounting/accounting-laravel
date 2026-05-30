<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\SalesReceipts\Pages;

use App\Filament\App\Resources\SalesReceipts\SalesReceiptResource;
use Filament\Resources\Pages\ListRecords;

class ListSalesReceipts extends ListRecords
{
    #[\Override]
    protected static string $resource = SalesReceiptResource::class;
}
