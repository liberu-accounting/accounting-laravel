<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\ApprovalRules\Pages;

use App\Filament\App\Resources\ApprovalRules\ApprovalRuleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListApprovalRules extends ListRecords
{
    #[\Override]
    protected static string $resource = ApprovalRuleResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
