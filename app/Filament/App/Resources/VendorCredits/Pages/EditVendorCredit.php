<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\VendorCredits\Pages;

use App\Filament\App\Resources\VendorCredits\VendorCreditResource;
use Filament\Resources\Pages\EditRecord;

class EditVendorCredit extends EditRecord
{
    #[\Override]
    protected static string $resource = VendorCreditResource::class;

    protected function afterSave(): void
    {
        $this->record->calculateTotals();
    }
}
