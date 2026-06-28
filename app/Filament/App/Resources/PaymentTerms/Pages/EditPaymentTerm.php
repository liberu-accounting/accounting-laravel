<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\PaymentTerms\Pages;

use App\Filament\App\Resources\PaymentTerms\PaymentTermResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPaymentTerm extends EditRecord
{
    #[\Override]
    protected static string $resource = PaymentTermResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
