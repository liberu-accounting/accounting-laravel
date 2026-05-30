<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\ChartOfAccounts\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\App\Resources\ChartOfAccounts\ChartOfAccountsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditChartOfAccounts extends EditRecord
{
    #[\Override]
    protected static string $resource = ChartOfAccountsResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
