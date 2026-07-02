<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\ReconciliationRules\Pages;

use App\Filament\App\Resources\ReconciliationRules\ReconciliationRuleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateReconciliationRule extends CreateRecord
{
    #[\Override]
    protected static string $resource = ReconciliationRuleResource::class;
}
