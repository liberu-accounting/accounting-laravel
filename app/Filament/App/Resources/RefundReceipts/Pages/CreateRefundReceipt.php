<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\RefundReceipts\Pages;

use App\Filament\App\Resources\RefundReceipts\RefundReceiptResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRefundReceipt extends CreateRecord
{
    #[\Override]
    protected static string $resource = RefundReceiptResource::class;

    protected function afterCreate(): void
    {
        $this->record->calculateTotals();
    }
}
