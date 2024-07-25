<?php

namespace App\Filament\App\Resources\GeneralLedgerReportResource\Pages;

use App\Filament\App\Resources\GeneralLedgerReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGeneralLedgerReports extends ListRecords
{
    protected static string $resource = GeneralLedgerReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}