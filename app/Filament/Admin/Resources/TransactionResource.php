<?php

namespace App\Filament\Admin\Resources;

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
use App\Filament\Admin\Resources\TransactionResource\Pages;
use App\Filament\Admin\Resources\TransactionResource\RelationManagers;

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
                BelongsToSelect::make('debit_account_id')
                    ->relationship('debitAccount', 'name')
                    ->label('Debit Account'),
                BelongsToSelect::make('credit_account_id')
                    ->relationship('creditAccount', 'name')
                    ->label('Credit Account'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('transaction_date')
                    ->label('Date')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('transaction_description')
                    ->label('Description')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('amount')->label('Amount')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('debit_account_id')
                    ->label('Debit Account')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('credit_account_id')
                    ->label('Credit Account')
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
