<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\RefundReceipts\Pages;

use App\Filament\App\Resources\RefundReceipts\RefundReceiptResource;
use Filament\Resources\Pages\EditRecord;

class EditRefundReceipt extends EditRecord
{
    #[\Override]
    protected static string $resource = RefundReceiptResource::class;

    protected function afterSave(): void
    {
        $this->record->calculateTotals();
    }
}
