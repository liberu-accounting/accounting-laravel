<?php

namespace App\Filament\App\Resources\BankStatementResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\App\Resources\BankStatementResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBankStatement extends EditRecord
{
    protected static string $resource = BankStatementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
