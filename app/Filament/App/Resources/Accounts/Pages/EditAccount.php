<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Accounts\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\App\Resources\Accounts\AccountResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAccount extends EditRecord
{
    #[\Override]
    protected static string $resource = AccountResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
