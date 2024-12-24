

<?php

namespace App\Filament\Resources;

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

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('amount')
                    ->required()
                    ->numeric()
                    ->prefix('$')
                    ->minValue(0.01)
                    ->step(0.01),
                Forms\Components\TextInput::make('description')
                    ->required()
                    ->maxLength(255),
                Forms\Components\DatePicker::make('date')
                    ->required()
                    ->maxDate(now()),
                Forms\Components\Select::make('approval_status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ])
                    ->disabled()
                    ->dehydrated(false),
                Forms\Components\Textarea::make('rejection_reason')
                    ->visible(fn (?Model $record) => $record?->approval_status === 'rejected')
                    ->maxLength(1000)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('amount')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->searchable(),
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Submitted By')
                    ->searchable(),
                Tables\Columns\TextColumn::make('approval_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'warning',
                    }),
                Tables\Columns\TextColumn::make('approver.name')
                    ->label('Approved By')
                    ->visible(fn (Model $record): bool => $record->approved_by !== null),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('approval_status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('approve')
                    ->action(fn (Expense $record) => $record->approve())
                    ->requiresConfirmation()
                    ->visible(fn (Expense $record) => $record->isPending())
                    ->color('success')
                    ->icon('heroicon-o-check'),
                Tables\Actions\Action::make('reject')
                    ->form([
                        Forms\Components\Textarea::make('reason')
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
            'index' => Pages\ListExpenses::route('/'),
            'create' => Pages\CreateExpense::route('/create'),
            'edit' => Pages\EditExpense::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('approval_status', 'pending')->count() ?: null;
    }
}