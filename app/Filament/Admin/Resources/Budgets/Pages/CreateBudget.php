<?php

namespace App\Filament\Admin\Resources\Budgets\Pages;

use App\Filament\Admin\Resources\Budgets\BudgetResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBudget extends CreateRecord
{
    protected static string $resource = BudgetResource::class;
}
