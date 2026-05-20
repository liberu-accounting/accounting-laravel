<?php

namespace App\Filament\App\Resources\SalesReceipts\Pages;

use App\Filament\App\Resources\SalesReceipts\SalesReceiptResource;
use Filament\Resources\Pages\EditRecord;

class EditSalesReceipt extends EditRecord
{
    protected static string $resource = SalesReceiptResource::class;

    protected function afterSave(): void
    {
        $this->record->calculateTotals();
    }
}
