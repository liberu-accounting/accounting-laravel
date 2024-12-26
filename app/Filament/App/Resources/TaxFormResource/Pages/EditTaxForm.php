

<?php

namespace App\Filament\App\Resources\TaxFormResource\Pages;

use App\Filament\App\Resources\TaxFormResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTaxForm extends EditRecord
{
    protected static string $resource = TaxFormResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}