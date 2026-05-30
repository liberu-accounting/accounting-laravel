<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Payments\Pages;

use Filament\Actions\CreateAction;
use App\Filament\App\Resources\Payments\PaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPayments extends ListRecords
{
    #[\Override]
    protected static string $resource = PaymentResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
