<?php

namespace App\Filament\App\Resources\AccountResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\App\Resources\AccountResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAccount extends EditRecord
{
    protected static string $resource = AccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
