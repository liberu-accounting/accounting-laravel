<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\TaxRates\Pages;

use App\Filament\App\Resources\TaxRates\TaxRateResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTaxRate extends CreateRecord
{
    #[\Override]
    protected static string $resource = TaxRateResource::class;
}
