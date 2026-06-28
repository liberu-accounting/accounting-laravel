<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Budgets\Pages;

use App\Filament\Admin\Resources\Budgets\BudgetResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBudgets extends ListRecords
{
    #[\Override]
    protected static string $resource = BudgetResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
