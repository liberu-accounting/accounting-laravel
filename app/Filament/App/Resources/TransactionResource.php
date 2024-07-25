<?php

namespace App\Filament\App\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\Transaction;
use Filament\Resources\Resource;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\BelongsToSelect;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Rules\DoubleEntryValidator;
use App\Filament\App\Resources\TransactionResource\Pages;
use App\Filament\App\Resources\TransactionResource\RelationManagers;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                DatePicker::make('transaction_date')
                    ->label('Date'),
                Textarea::make('transaction_description')
                    ->label('Description'),
                TextInput::make('amount')
                    ->numeric()
                    ->label('Amount')
                    ->rules(['required', new DoubleEntryValidator()]),
                BelongsToSelect::make('currency_id')
                    ->relationship('currency', 'code')
                    ->label('Currency')
                    ->required(),
                TextInput::make('exchange_rate')
                    ->numeric()
                    ->label('Exchange Rate')
                    ->helperText('Leave empty for default currency'),
                BelongsToSelect::make('debit_account_id')
                    ->relationship('debitAccount', 'name')
                    ->label('Debit Account'),
                BelongsToSelect::make('credit_account_id')
                    ->relationship('creditAccount', 'name')
                    ->label('Credit Account'),
                Forms\Components\Toggle::make('reconciled')
                    ->label('Reconciled'),
                Forms\Components\Textarea::make('discrepancy_notes')
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
            ->actions([
                EditAction::make(),
                ViewAction::make(),
            ])
            ->bulkActions([
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
            'index' => Pages\ListTransactions::route('/'),
            'create' => Pages\CreateTransaction::route('/create'),
            'edit' => Pages\EditTransaction::route('/{record}/edit'),
        ];
    }
}
