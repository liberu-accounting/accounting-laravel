<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExpenseResource\Pages;
use App\Models\Expense;
use App\Models\Supplier;
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
                Forms\Components\Select::make('supplier_id')
                    ->relationship('supplier', 'supplier_first_name', fn ($query) => $query->orderBy('supplier_first_name'))
                    ->searchable()
                    ->preload()
                    ->label('Supplier')
                    ->createOptionForm([
                        Forms\Components\TextInput::make('supplier_first_name')
                            ->required()
                            ->label('First Name'),
                        Forms\Components\TextInput::make('supplier_last_name')
                            ->required()
                            ->label('Last Name'),
                        Forms\Components\TextInput::make('supplier_email')
                            ->email()
                            ->label('Email'),
                        Forms\Components\TextInput::make('supplier_phone_number')
                            ->tel()
                            ->label('Phone Number'),
                    ]),
                Forms\Components\BelongsToSelect::make('currency_id')
                    ->relationship('currency', 'code')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->default(fn () => Currency::where('is_default', true)->first()?->currency_id)
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set, $get) {
                        if ($state && $get('amount')) {
                            $defaultCurrency = Currency::where('is_default', true)->first();
                            if ($state !== $defaultCurrency->currency_id) {
                                $exchangeRateService = app(ExchangeRateService::class);
                                $rate = $exchangeRateService->getExchangeRate(
                                    Currency::find($state),
                                    $defaultCurrency
                                );
                                $set('amount', $get('amount') * $rate);
                            }
                        }
                    }),
                Forms\Components\TextInput::make('amount')
                    ->required()
                    ->numeric()
                    ->prefix(fn ($get) => Currency::find($get('currency_id'))?->symbol ?? '$')
                    ->minValue(0.01)
                    ->step(0.01),
                Forms\Components\TextInput::make('description')
                    ->required()
                    ->maxLength(255),
                Forms\Components\DatePicker::make('date')
                    ->required()
                    ->maxDate(now()),
                Forms\Components\Select::make('categories')
                    ->multiple()
                    ->relationship('categories', 'name')
                    ->preload()
                    ->required(),
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
                Tables\Columns\TextColumn::make('supplier.supplier_first_name')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('currency.code')
                    ->label('Currency')
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->money(fn ($record) => $record->currency?->code ?? 'USD')
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
                Tables\Columns\TextColumn::make('categories.name')
                    ->badge()
                    ->separator(',')
                    ->searchable(),
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
                Tables\Filters\SelectFilter::make('categories')
                    ->relationship('categories', 'name')
                    ->multiple()
                    ->preload(),
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