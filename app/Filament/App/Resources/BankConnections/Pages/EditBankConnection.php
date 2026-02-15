<?php

namespace App\Filament\App\Resources\BankConnections\Pages;

use App\Filament\App\Resources\BankConnections\BankConnectionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBankConnection extends EditRecord
{
    protected static string $resource = BankConnectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
