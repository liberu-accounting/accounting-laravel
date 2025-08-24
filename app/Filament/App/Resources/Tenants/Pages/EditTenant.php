<?php

namespace App\Filament\App\Resources\Tenants\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\App\Resources\Tenants\TenantResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTenant extends EditRecord
{
    protected static string $resource = TenantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
