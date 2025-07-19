<?php

namespace App\Filament\Resources;

use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use App\Filament\Resources\ExpenseResource\Pages\ListExpenses;
use App\Filament\Resources\ExpenseResource\Pages\CreateExpense;
use App\Filament\Resources\ExpenseResource\Pages\EditExpense;
use App\Filament\Resources\ExpenseResource\Pages;
use App\Models\Expense;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationGroup = 'Finance';

    public static function form(Form $form): Form
    {
        return $form
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
                    ->visible(fn (?Model $record) => $record?->approval_status === 'rejected')
                    ->maxLength(1000)
                    ->columnSpanFull(),
                Toggle::make('is_recurring')
                    ->label('Recurring Expense')
                    ->reactive(),
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
                    ->visible(fn (Expense $record) => $record->isPending())
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
                    ->visible(fn (Expense $record) => $record->isPending())
                    ->color('danger')
                    ->icon('heroicon-o-x-mark'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

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
