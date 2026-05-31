<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\VendorCredits\Pages;

use App\Filament\App\Resources\VendorCredits\VendorCreditResource;
use Filament\Resources\Pages\CreateRecord;

class CreateVendorCredit extends CreateRecord
{
    #[\Override]
    protected static string $resource = VendorCreditResource::class;

    protected function afterCreate(): void
    {
        $this->record->calculateTotals();
    }
}
