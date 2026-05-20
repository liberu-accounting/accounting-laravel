<?php

namespace App\Filament\App\Resources\TaxRates\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\App\Resources\TaxRates\TaxRateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTaxRate extends EditRecord
{
    protected static string $resource = TaxRateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
