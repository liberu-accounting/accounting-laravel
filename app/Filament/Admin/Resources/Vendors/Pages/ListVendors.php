<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Vendors\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Admin\Resources\Vendors\VendorResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVendors extends ListRecords
{
    #[\Override]
    protected static string $resource = VendorResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
