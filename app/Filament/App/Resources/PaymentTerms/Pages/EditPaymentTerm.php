<?php

namespace App\Filament\App\Resources\PaymentTerms\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\App\Resources\PaymentTerms\PaymentTermResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPaymentTerm extends EditRecord
{
    protected static string $resource = PaymentTermResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
