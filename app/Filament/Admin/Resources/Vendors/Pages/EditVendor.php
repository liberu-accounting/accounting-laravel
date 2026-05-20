<?php

namespace App\Filament\Admin\Resources\Vendors\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Admin\Resources\Vendors\VendorResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVendor extends EditRecord
{
    protected static string $resource = VendorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
