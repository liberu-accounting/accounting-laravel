<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\TaxRates\Pages;

use App\Filament\App\Resources\TaxRates\TaxRateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTaxRates extends ListRecords
{
    #[\Override]
    protected static string $resource = TaxRateResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
