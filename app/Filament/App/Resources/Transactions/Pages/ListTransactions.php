<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Transactions\Pages;

use App\Filament\App\Resources\Transactions\TransactionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTransactions extends ListRecords
{
    #[\Override]
    protected static string $resource = TransactionResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
