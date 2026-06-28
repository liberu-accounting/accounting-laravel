<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Accounts\Pages;

use App\Filament\App\Resources\Accounts\AccountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAccounts extends ListRecords
{
    #[\Override]
    protected static string $resource = AccountResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
