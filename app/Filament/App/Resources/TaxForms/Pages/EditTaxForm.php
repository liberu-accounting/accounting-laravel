<?php

namespace App\Filament\App\Resources\TaxForms\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\App\Resources\TaxForms\TaxFormResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTaxForm extends EditRecord
{
    protected static string $resource = TaxFormResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
