<?php

namespace App\Filament\App\Resources\PaymentTermResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\App\Resources\PaymentTermResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPaymentTerms extends ListRecords
{
    protected static string $resource = PaymentTermResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
