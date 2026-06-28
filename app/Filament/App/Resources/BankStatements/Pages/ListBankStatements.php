<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\BankStatements\Pages;

use App\Filament\App\Resources\BankStatements\BankStatementResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBankStatements extends ListRecords
{
    #[\Override]
    protected static string $resource = BankStatementResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
