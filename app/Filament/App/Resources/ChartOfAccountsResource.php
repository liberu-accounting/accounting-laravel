<?php

namespace App\Filament\App\Resources;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IndentedTextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use App\Filament\App\Resources\ChartOfAccountsResource\Pages\ListChartOfAccounts;
use App\Filament\App\Resources\ChartOfAccountsResource\Pages\CreateChartOfAccounts;
use App\Filament\App\Resources\ChartOfAccountsResource\Pages\EditChartOfAccounts;
use App\Filament\App\Resources\ChartOfAccountsResource\Pages;
use App\Models\Account;
use App\Models\AccountTemplate;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class ChartOfAccountsResource extends Resource
{
    protected static ?string $model = Account::class;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('template_id')
                    ->label('Industry Template')
                    ->options(AccountTemplate::pluck('name', 'id'))
                    ->reactive()
                    ->visible(fn ($get) => !$get('parent_id')),
                
                TextInput::make('account_name')
                    ->required()
                    ->maxLength(255),
                    
                TextInput::make('account_number')
                    ->required()
                    ->numeric(),
                    
                Select::make('account_type')
                    ->required()
                    ->options([
                        'asset' => 'Asset',
                        'liability' => 'Liability',
                        'equity' => 'Equity',
                        'revenue' => 'Revenue',
                        'expense' => 'Expense'
                    ]),
                    
                Select::make('parent_id')
                    ->label('Parent Account')
                    ->options(fn () => Account::whereNull('parent_id')
                        ->pluck('account_name', 'account_id'))
                    ->searchable(),
                    
                TextInput::make('balance')
                    ->numeric()
                    ->disabled(fn ($get) => $get('parent_id')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('account_number')
                    ->sortable(),
                IndentedTextColumn::make('account_name')
                    ->indentedFromField('parent_id'),
                TextColumn::make('account_type'),
                TextColumn::make('balance')
                    ->money(),
            ])
            ->defaultSort('account_number')
            ->filters([
                SelectFilter::make('account_type')
                    ->options([
                        'asset' => 'Asset',
                        'liability' => 'Liability',
                        'equity' => 'Equity',
                        'revenue' => 'Revenue',
                        'expense' => 'Expense'
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListChartOfAccounts::route('/'),
            'create' => CreateChartOfAccounts::route('/create'),
            'edit' => EditChartOfAccounts::route('/{record}/edit'),
        ];
    }
}
