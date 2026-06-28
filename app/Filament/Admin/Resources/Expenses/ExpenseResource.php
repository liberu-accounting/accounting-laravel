<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Expenses;

use App\Filament\Admin\Resources\ExpenseResource\Pages;
use App\Filament\Admin\Resources\Expenses\Pages\CreateExpense;
use App\Filament\Admin\Resources\Expenses\Pages\EditExpense;
use App\Filament\Admin\Resources\Expenses\Pages\ListExpenses;
use App\Models\Expense;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ExpenseResource extends Resource
{
    #[\Override]
    protected static ?string $model = Expense::class;

    #[\Override]
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-currency-dollar';
    #[\Override]
    protected static string | \UnitEnum | null $navigationGroup = 'Finance';

    #[\Override]
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('amount')
                    ->required()
                    ->numeric()
                    ->prefix('$')
                    ->minValue(0.01)
                    ->step(0.01),
                TextInput::make('description')
                    ->required()
                    ->maxLength(255),
                DatePicker::make('date')
                    ->required()
                    ->maxDate(now()),
                Select::make('approval_status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ])
                    ->disabled()
                    ->dehydrated(false),
                Textarea::make('rejection_reason')
                    ->visible(fn (?Model $record): bool => $record?->approval_status === 'rejected')
                    ->maxLength(1000)
                    ->columnSpanFull(),
                Toggle::make('is_recurring')
                    ->label('Recurring Expense')
                    ->live(),
                Select::make('recurrence_frequency')
                    ->options([
                        'daily' => 'Daily',
                        'weekly' => 'Weekly',
                        'monthly' => 'Monthly',
                        'yearly' => 'Yearly',
                    ])
                    ->visible(fn ($get) => $get('is_recurring'))
                    ->required(fn ($get) => $get('is_recurring')),
                DatePicker::make('recurrence_start')
                    ->label('Start Date')
                    ->visible(fn ($get) => $get('is_recurring'))
                    ->required(fn ($get) => $get('is_recurring')),
                DatePicker::make('recurrence_end')
                    ->label('End Date')
                    ->visible(fn ($get) => $get('is_recurring'))
                    ->minDate(fn ($get) => $get('recurrence_start')),
            ]);
    }

    #[\Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('amount')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('description')
                    ->searchable(),
                TextColumn::make('date')
                    ->date()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Submitted By')
                    ->searchable(),
                TextColumn::make('approval_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'warning',
                    }),
                TextColumn::make('approver.name')
                    ->label('Approved By')
                    ->visible(fn (Model $record): bool => $record->approved_by !== null),
            ])
            ->filters([
                SelectFilter::make('approval_status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('approve')
                    ->action(fn (Expense $record) => $record->approve())
                    ->requiresConfirmation()
                    ->visible(fn (Expense $record): bool => $record->isPending())
                    ->color('success')
                    ->icon('heroicon-o-check'),
                Action::make('reject')
                    ->schema([
                        Textarea::make('reason')
                            ->required()
                            ->maxLength(1000)
                            ->label('Rejection Reason'),
                    ])
                    ->action(fn (Expense $record, array $data) => $record->reject($data['reason']))
                    ->visible(fn (Expense $record): bool => $record->isPending())
                    ->color('danger')
                    ->icon('heroicon-o-x-mark'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    #[\Override]
    public static function getRelations(): array
    {
        return [];
    }

    #[\Override]
    public static function getPages(): array
    {
        return [
            'index' => ListExpenses::route('/'),
            'create' => CreateExpense::route('/create'),
            'edit' => EditExpense::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('approval_status', 'pending')->count() ?: null;
    }
}
