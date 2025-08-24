<?php

namespace App\Filament\Resources\Budgets;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\Action;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\Budgets\Pages\ListBudgets;
use App\Filament\Resources\Budgets\Pages\CreateBudget;
use App\Filament\Resources\Budgets\Pages\EditBudget;
use App\Filament\Resources\BudgetResource\Pages;
use App\Models\Budget;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Services\BudgetService;

class BudgetResource extends Resource
{
    protected static ?string $model = Budget::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-calculator';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('account_id')
                    ->relationship('account', 'name')
                    ->required(),
                DatePicker::make('start_date')
                    ->required(),
                DatePicker::make('end_date')
                    ->required(),
                TextInput::make('planned_amount')
                    ->numeric()
                    ->required(),
                TextInput::make('forecast_amount')
                    ->numeric()
                    ->disabled(),
                Toggle::make('is_approved')
                    ->label('Approved'),
                TextInput::make('description')
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('account.name'),
                TextColumn::make('start_date')
                    ->date(),
                TextColumn::make('end_date')
                    ->date(),
                TextColumn::make('planned_amount')
                    ->money(),
                TextColumn::make('forecast_amount')
                    ->money(),
                TextColumn::make('variance')
                    ->money(),
                IconColumn::make('is_approved')
                    ->boolean(),
                TextColumn::make('description'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
                Action::make('generate_forecast')
                    ->action(function (Budget $record) {
                        $budgetService = new BudgetService();
                        $budgetService->generateForecast($record);
                    })
                    ->requiresConfirmation()
                    ->color('success')
                    ->icon('heroicon-o-calculator'),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBudgets::route('/'),
            'create' => CreateBudget::route('/create'),
            'edit' => EditBudget::route('/{record}/edit'),
        ];
    }
}
