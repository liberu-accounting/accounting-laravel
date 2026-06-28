<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\ChartOfAccounts\Pages;

use App\Filament\App\Resources\ChartOfAccounts\ChartOfAccountsResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListChartOfAccounts extends ListRecords
{
    #[\Override]
    protected static string $resource = ChartOfAccountsResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
