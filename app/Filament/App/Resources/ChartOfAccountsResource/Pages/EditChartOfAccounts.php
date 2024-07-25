<?php

namespace App\Filament\App\Resources\ChartOfAccountsResource\Pages;

use App\Filament\App\Resources\ChartOfAccountsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditChartOfAccounts extends EditRecord
{
    protected static string $resource = ChartOfAccountsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}