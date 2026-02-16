<?php

namespace App\Filament\App\Resources\HmrcVatReturns\Pages;

use App\Filament\App\Resources\HmrcVatReturns\HmrcVatReturnResource;
use Filament\Resources\Pages\CreateRecord;

class CreateHmrcVatReturn extends CreateRecord
{
    protected static string $resource = HmrcVatReturnResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Ensure company_id is set
        $data['company_id'] = auth()->user()->currentTeam->company_id ?? 1;
        
        return $data;
    }
}
