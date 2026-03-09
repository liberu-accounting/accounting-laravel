<?php

namespace App\Filament\Admin\Resources\Vendors\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Admin\Resources\Vendors\VendorResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVendors extends ListRecords
{
    protected static string $resource = VendorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
