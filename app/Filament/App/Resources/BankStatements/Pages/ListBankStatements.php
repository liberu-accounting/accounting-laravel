<?php

namespace App\Filament\App\Resources\BankStatements\Pages;

use Filament\Actions\CreateAction;
use App\Filament\App\Resources\BankStatements\BankStatementResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBankStatements extends ListRecords
{
    protected static string $resource = BankStatementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
