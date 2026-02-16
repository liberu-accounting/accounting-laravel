<?php

namespace App\Filament\App\Resources\RefundReceipts\Pages;

use App\Filament\App\Resources\RefundReceipts\RefundReceiptResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRefundReceipt extends CreateRecord
{
    protected static string $resource = RefundReceiptResource::class;

    protected function afterCreate(): void
    {
        $this->record->calculateTotals();
    }
}
