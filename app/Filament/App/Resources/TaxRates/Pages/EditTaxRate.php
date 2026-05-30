<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\TaxRates\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\App\Resources\TaxRates\TaxRateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTaxRate extends EditRecord
{
    #[\Override]
    protected static string $resource = TaxRateResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
