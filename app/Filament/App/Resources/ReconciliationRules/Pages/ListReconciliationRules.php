<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\ReconciliationRules\Pages;

use App\Filament\App\Resources\ReconciliationRules\ReconciliationRuleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListReconciliationRules extends ListRecords
{
    #[\Override]
    protected static string $resource = ReconciliationRuleResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
