<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\BankStatementResource\Pages;
use App\Models\BankStatement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;

class BankStatementResource extends Resource
{
    protected static ?string $model = BankStatement::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('statement_date')
                    ->required(),
                Forms\Components\Select::make('account_id')
                    ->relationship('account', 'name')
                    ->required(),
                Forms\Components\TextInput::make('total_credits')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('total_debits')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('ending_balance')
                    ->required()
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('statement_date'),
                Tables\Columns\TextColumn::make('account.name'),
                Tables\Columns\TextColumn::make('total_credits'),
                Tables\Columns\TextColumn::make('total_debits'),
                Tables\Columns\TextColumn::make('ending_balance'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListBankStatements::route('/'),
            'create' => Pages\CreateBankStatement::route('/create'),
            'edit' => Pages\EditBankStatement::route('/{record}/edit'),
        ];
    }
}
