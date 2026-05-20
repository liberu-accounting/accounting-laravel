<?php

namespace App\Filament\App\Resources\BankConnections\Pages;

use App\Filament\App\Resources\BankConnections\BankConnectionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBankConnection extends CreateRecord
{
    protected static string $resource = BankConnectionResource::class;
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        
        return $data;
    }
}
