<?php

namespace App\Filament\App\Resources\TenantResource\Pages;

use App\Filament\App\Resources\TenantResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;
}
