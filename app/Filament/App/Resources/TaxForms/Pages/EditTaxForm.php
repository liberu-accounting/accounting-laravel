<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\TaxForms\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\App\Resources\TaxForms\TaxFormResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTaxForm extends EditRecord
{
    #[\Override]
    protected static string $resource = TaxFormResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
