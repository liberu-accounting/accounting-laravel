<?php

namespace App\Filament\App\Resources\GeneralLedgerReports\Pages;

use Filament\Actions\CreateAction;
use App\Filament\App\Resources\GeneralLedgerReports\GeneralLedgerReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGeneralLedgerReports extends ListRecords
{
    protected static string $resource = GeneralLedgerReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}