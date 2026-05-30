<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\TaxForms\Pages;

use App\Filament\App\Resources\TaxForms\TaxFormResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTaxForm extends CreateRecord
{
    #[\Override]
    protected static string $resource = TaxFormResource::class;
}
