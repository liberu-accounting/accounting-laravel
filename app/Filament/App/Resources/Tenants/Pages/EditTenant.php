<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Tenants\Pages;

use App\Filament\App\Resources\Tenants\TenantResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTenant extends EditRecord
{
    #[\Override]
    protected static string $resource = TenantResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
