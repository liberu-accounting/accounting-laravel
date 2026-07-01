<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\ApprovalRules\Pages;

use App\Filament\App\Resources\ApprovalRules\ApprovalRuleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditApprovalRule extends EditRecord
{
    #[\Override]
    protected static string $resource = ApprovalRuleResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
