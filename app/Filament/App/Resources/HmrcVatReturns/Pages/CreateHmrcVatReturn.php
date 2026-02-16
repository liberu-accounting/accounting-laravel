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
        $companyId = auth()->user()->currentTeam->company_id ?? null;
        
        if (!$companyId) {
            throw new \Exception('No company associated with current user/team');
        }
        
        $data['company_id'] = $companyId;
        
        return $data;
    }
}
