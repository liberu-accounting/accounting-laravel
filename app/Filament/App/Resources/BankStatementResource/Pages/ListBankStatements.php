<?php

namespace App\Filament\App\Resources\BankStatementResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\App\Resources\BankStatementResource;
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
