<?php

namespace App\Filament\App\Resources;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use App\Filament\App\Resources\TransactionResource\Pages\ListTransactions;
use App\Filament\App\Resources\TransactionResource\Pages\CreateTransaction;
use App\Filament\App\Resources\TransactionResource\Pages\EditTransaction;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\Transaction;
use App\Models\Currency;
use Filament\Resources\Resource;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Rules\DoubleEntryValidator;
use App\Services\ExchangeRateService;
use App\Filament\App\Resources\TransactionResource\Pages;
use App\Filament\App\Resources\TransactionResource\RelationManagers;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('transaction_date')
                    ->label('Date')
                    ->required(),
                Textarea::make('transaction_description')
                    ->label('Description')
                    ->required(),
                TextInput::make('amount')
                    ->numeric()
                    ->label('Amount')
                    ->required()
                    ->rules(['required', new DoubleEntryValidator()])
                    ->step('0.01'),
                Select::make('currency_id')
                    ->relationship('currency', 'code')
                    ->label('Currency')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if ($state) {
                            $defaultCurrency = Currency::where('is_default', true)->first();
                            if ($state !== $defaultCurrency->currency_id) {
                                $exchangeRateService = app(ExchangeRateService::class);
                                $rate = $exchangeRateService->getExchangeRate(
                                    Currency::find($state),
                                    $defaultCurrency
                                );
                                $set('exchange_rate', $rate);
                            } else {
                                $set('exchange_rate', 1);
                            }
                        }
                    }),
                TextInput::make('exchange_rate')
                    ->numeric()
                    ->label('Exchange Rate')
                    ->required()
                    ->default(1)
                    ->step('0.000001')
                    ->helperText('Exchange rate to default currency'),
                Select::make('debit_account_id')
                    ->relationship('debitAccount', 'account_name')
                    ->label('Debit Account')
                    ->required()
                    ->searchable()
                    ->preload(),
                Select::make('credit_account_id')
                    ->relationship('creditAccount', 'account_name')
                    ->label('Credit Account')
                    ->required()
                    ->searchable()
                    ->preload(),
                Toggle::make('reconciled')
                    ->label('Reconciled'),
                Textarea::make('discrepancy_notes')
                    ->label('Discrepancy Notes'),
            ]);
    }
    
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('transaction_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('transaction_description')
                    ->label('Description')
                    ->searchable()
                    ->limit(30),
                TextColumn::make('amount')
                    ->label('Amount')
                    ->money('usd')
                    ->sortable(),
                TextColumn::make('debitAccount.name')
                    ->label('Debit Account')
                    ->searchable(),
                TextColumn::make('creditAccount.name')
                    ->label('Credit Account')
                    ->searchable(),
                IconColumn::make('reconciled')
                    ->boolean()
                    ->label('Reconciled')
                    ->tooltip('Reconciliation Status'),
                TextColumn::make('discrepancy_notes')
                    ->label('Notes')
                    ->searchable()
                    ->limit(20),
            ])
            ->filters([
                SelectFilter::make('reconciled')
                    ->options([
                        true => 'Reconciled',
                        false => 'Not Reconciled',
                    ]),
                DateRangeFilter::make('transaction_date'),
            ])
            ->recordActions([
                EditAction::make(),
                ViewAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ExportBulkAction::make(),
                ]),
            ])
            ->defaultSort('transaction_date', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTransactions::route('/'),
            'create' => CreateTransaction::route('/create'),
            'edit' => EditTransaction::route('/{record}/edit'),
        ];
    }
}
