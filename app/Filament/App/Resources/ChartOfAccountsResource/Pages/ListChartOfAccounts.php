<?php

namespace App\Filament\App\Resources\ChartOfAccountsResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\App\Resources\ChartOfAccountsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListChartOfAccounts extends ListRecords
{
    protected static string $resource = ChartOfAccountsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}