<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Budgets\Pages;

use App\Filament\Admin\Resources\Budgets\BudgetResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBudget extends CreateRecord
{
    #[\Override]
    protected static string $resource = BudgetResource::class;
}
