<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\GeneralLedgerReports\Pages;

use App\Filament\App\Resources\GeneralLedgerReports\GeneralLedgerReportResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGeneralLedgerReport extends CreateRecord
{
    #[\Override]
    protected static string $resource = GeneralLedgerReportResource::class;
}
