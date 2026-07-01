<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\ApprovalRules\Pages;

use App\Filament\App\Resources\ApprovalRules\ApprovalRuleResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateApprovalRule extends CreateRecord
{
    #[\Override]
    protected static string $resource = ApprovalRuleResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    #[\Override]
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // ApprovalRule can't use Filament's automatic tenant stamping (see
        // ApprovalRuleResource) — stamp team_id from the current tenant here.
        $data['team_id'] = Filament::getTenant()?->getKey();

        return $data;
    }
}
