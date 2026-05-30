<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Tenants\Pages;

use Filament\Actions\CreateAction;
use App\Filament\App\Resources\Tenants\TenantResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTenants extends ListRecords
{
    #[\Override]
    protected static string $resource = TenantResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
