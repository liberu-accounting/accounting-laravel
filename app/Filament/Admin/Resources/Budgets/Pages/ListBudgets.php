<?php

namespace App\Filament\Admin\Resources\Budgets\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Admin\Resources\Budgets\BudgetResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBudgets extends ListRecords
{
    protected static string $resource = BudgetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
