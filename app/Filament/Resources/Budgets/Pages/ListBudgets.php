<?php

namespace App\Filament\Resources\Budgets\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\Budgets\BudgetResource;
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
