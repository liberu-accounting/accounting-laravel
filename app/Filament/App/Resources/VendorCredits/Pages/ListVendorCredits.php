<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\VendorCredits\Pages;

use App\Filament\App\Resources\VendorCredits\VendorCreditResource;
use Filament\Resources\Pages\ListRecords;

class ListVendorCredits extends ListRecords
{
    #[\Override]
    protected static string $resource = VendorCreditResource::class;
}
