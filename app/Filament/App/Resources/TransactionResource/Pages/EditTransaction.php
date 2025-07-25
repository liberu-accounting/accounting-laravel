<?php

namespace App\Filament\App\Resources\TransactionResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\App\Resources\TransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTransaction extends EditRecord
{
    protected static string $resource = TransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
