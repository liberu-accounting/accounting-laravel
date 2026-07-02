<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\ReconciliationRules\Pages;

use App\Filament\App\Resources\ReconciliationRules\ReconciliationRuleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditReconciliationRule extends EditRecord
{
    #[\Override]
    protected static string $resource = ReconciliationRuleResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
