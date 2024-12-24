<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\ChartOfAccountsResource\Pages;
use App\Models\Account;
use App\Models\AccountTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class ChartOfAccountsResource extends Resource
{
    protected static ?string $model = Account::class;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('template_id')
                    ->label('Industry Template')
                    ->options(AccountTemplate::pluck('name', 'id'))
                    ->reactive()
                    ->visible(fn ($get) => !$get('parent_id')),
                
                Forms\Components\TextInput::make('account_name')
                    ->required()
                    ->maxLength(255),
                    
                Forms\Components\TextInput::make('account_number')
                    ->required()
                    ->numeric(),
                    
                Forms\Components\Select::make('account_type')
                    ->required()
                    ->options([
                        'asset' => 'Asset',
                        'liability' => 'Liability',
                        'equity' => 'Equity',
                        'revenue' => 'Revenue',
                        'expense' => 'Expense'
                    ]),
                    
                Forms\Components\Select::make('parent_id')
                    ->label('Parent Account')
                    ->options(fn () => Account::whereNull('parent_id')
                        ->pluck('account_name', 'account_id'))
                    ->searchable(),
                    
                Forms\Components\TextInput::make('balance')
                    ->numeric()
                    ->disabled(fn ($get) => $get('parent_id')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('account_number')
                    ->sortable(),
                Tables\Columns\IndentedTextColumn::make('account_name')
                    ->indentedFromField('parent_id'),
                Tables\Columns\TextColumn::make('account_type'),
                Tables\Columns\TextColumn::make('balance')
                    ->money(),
            ])
            ->defaultSort('account_number')
            ->filters([
                Tables\Filters\SelectFilter::make('account_type')
                    ->options([
                        'asset' => 'Asset',
                        'liability' => 'Liability',
                        'equity' => 'Equity',
                        'revenue' => 'Revenue',
                        'expense' => 'Expense'
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListChartOfAccounts::route('/'),
            'create' => Pages\CreateChartOfAccounts::route('/create'),
            'edit' => Pages\EditChartOfAccounts::route('/{record}/edit'),
        ];
    }
}
