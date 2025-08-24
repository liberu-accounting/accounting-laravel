<?php

namespace App\Filament\App\Resources\AccountResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\App\Resources\AccountResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAccounts extends ListRecords
{
    protected static string $resource = AccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
