<?php

namespace App\Filament\App\Resources\RefundReceipts\Pages;

use App\Filament\App\Resources\RefundReceipts\RefundReceiptResource;
use Filament\Resources\Pages\ListRecords;

class ListRefundReceipts extends ListRecords
{
    protected static string $resource = RefundReceiptResource::class;
}
