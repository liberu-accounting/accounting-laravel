<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\PaymentTerms\Pages;

use App\Filament\App\Resources\PaymentTerms\PaymentTermResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPaymentTerms extends ListRecords
{
    #[\Override]
    protected static string $resource = PaymentTermResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
