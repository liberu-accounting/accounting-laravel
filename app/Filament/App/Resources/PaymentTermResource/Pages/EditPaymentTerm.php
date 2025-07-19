<?php

namespace App\Filament\App\Resources\PaymentTermResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\App\Resources\PaymentTermResource;
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
