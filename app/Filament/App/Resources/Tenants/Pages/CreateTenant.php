<?php

namespace App\Filament\App\Resources\Tenants\Pages;

use App\Filament\App\Resources\Tenants\TenantResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;
}
