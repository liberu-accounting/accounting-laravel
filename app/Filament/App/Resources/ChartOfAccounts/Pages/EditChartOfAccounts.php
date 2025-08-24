<?php

namespace App\Filament\App\Resources\ChartOfAccounts\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\App\Resources\ChartOfAccounts\ChartOfAccountsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditChartOfAccounts extends EditRecord
{
    protected static string $resource = ChartOfAccountsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}