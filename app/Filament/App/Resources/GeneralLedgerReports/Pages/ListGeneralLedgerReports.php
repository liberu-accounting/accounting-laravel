<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\GeneralLedgerReports\Pages;

use Filament\Actions\CreateAction;
use App\Filament\App\Resources\GeneralLedgerReports\GeneralLedgerReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGeneralLedgerReports extends ListRecords
{
    #[\Override]
    protected static string $resource = GeneralLedgerReportResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
