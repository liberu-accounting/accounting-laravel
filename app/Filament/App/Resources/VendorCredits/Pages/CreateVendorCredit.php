<?php

namespace App\Filament\App\Resources\VendorCredits\Pages;

use App\Filament\App\Resources\VendorCredits\VendorCreditResource;
use Filament\Resources\Pages\CreateRecord;

class CreateVendorCredit extends CreateRecord
{
    protected static string $resource = VendorCreditResource::class;

    protected function afterCreate(): void
    {
        $this->record->calculateTotals();
    }
}
