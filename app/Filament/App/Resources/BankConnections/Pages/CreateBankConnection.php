<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\BankConnections\Pages;

use App\Filament\App\Resources\BankConnections\BankConnectionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBankConnection extends CreateRecord
{
    #[\Override]
    protected static string $resource = BankConnectionResource::class;

    #[\Override]
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();

        return $data;
    }
}
