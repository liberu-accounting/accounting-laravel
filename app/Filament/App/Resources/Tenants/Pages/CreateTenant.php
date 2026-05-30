<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Tenants\Pages;

use App\Filament\App\Resources\Tenants\TenantResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTenant extends CreateRecord
{
    #[\Override]
    protected static string $resource = TenantResource::class;
}
