<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\TaxForms\Pages;

use App\Filament\App\Resources\TaxForms\TaxFormResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTaxForms extends ListRecords
{
    #[\Override]
    protected static string $resource = TaxFormResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
