<?php

namespace App\Filament\App\Resources\TaxRateResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\App\Resources\TaxRateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTaxRates extends ListRecords
{
    protected static string $resource = TaxRateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
