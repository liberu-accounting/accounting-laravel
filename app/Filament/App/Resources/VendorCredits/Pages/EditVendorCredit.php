<?php

namespace App\Filament\App\Resources\VendorCredits\Pages;

use App\Filament\App\Resources\VendorCredits\VendorCreditResource;
use Filament\Resources\Pages\EditRecord;

class EditVendorCredit extends EditRecord
{
    protected static string $resource = VendorCreditResource::class;

    protected function afterSave(): void
    {
        $this->record->calculateTotals();
    }
}
