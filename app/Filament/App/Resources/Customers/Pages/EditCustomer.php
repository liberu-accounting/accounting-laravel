<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Customers\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\App\Resources\Customers\CustomerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCustomer extends EditRecord
{
    #[\Override]
    protected static string $resource = CustomerResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
