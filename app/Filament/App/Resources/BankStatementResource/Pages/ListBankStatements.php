<?php

namespace App\Filament\Admin\Resources\BankStatementResource\Pages;

use App\Filament\Admin\Resources\BankStatementResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBankStatements extends ListRecords
{
    protected static string $resource = BankStatementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}