<?php

namespace App\Filament\App\Resources\TaxForms\Pages;

use Filament\Actions\CreateAction;
use App\Filament\App\Resources\TaxForms\TaxFormResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTaxForms extends ListRecords
{
    protected static string $resource = TaxFormResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
