<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\ChartOfAccounts;

use App\Filament\App\Resources\ChartOfAccounts\Pages\CreateChartOfAccounts;
use App\Filament\App\Resources\ChartOfAccounts\Pages\EditChartOfAccounts;
use App\Filament\App\Resources\ChartOfAccounts\Pages\ListChartOfAccounts;
use App\Models\Account;
use App\Models\AccountTemplate;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\IndentedTextColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ChartOfAccountsResource extends Resource
{
    #[\Override]
    protected static ?string $model = Account::class;

    #[\Override]
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('template_id')
                    ->label('Industry Template')
                    ->options(AccountTemplate::pluck('name', 'id'))
                    ->live()
                    ->visible(fn ($get): bool => ! $get('parent_id')),

                TextInput::make('account_number')
                    ->required()
                    ->numeric()
                    ->unique(ignoreRecord: true)
                    ->label('Account Number'),

                TextInput::make('account_name')
                    ->required()
                    ->maxLength(255)
                    ->label('Account Name'),

                Textarea::make('description')
                    ->maxLength(500)
                    ->rows(2)
                    ->label('Description'),

                Select::make('account_type')
                    ->required()
                    ->live()
                    ->options([
                        'asset' => 'Asset',
                        'liability' => 'Liability',
                        'equity' => 'Equity',
                        'revenue' => 'Revenue',
                        'expense' => 'Expense',
                    ])
                    ->label('Account Type'),

                Select::make('normal_balance')
                    ->required()
                    ->options([
                        'debit' => 'Debit',
                        'credit' => 'Credit',
                    ])
                    ->default(fn ($get): string => in_array($get('account_type'), ['asset', 'expense']) ? 'debit' : 'credit'
                    )
                    ->label('Normal Balance'),

                Select::make('parent_id')
                    ->label('Parent Account')
                    ->options(fn () => Account::whereNull('parent_id')
                        ->orderBy('account_number')
                        ->get()
                        ->mapWithKeys(fn ($account): array => [$account->id => $account->account_number.' - '.$account->account_name]))
                    ->searchable(),

                TextInput::make('opening_balance')
                    ->numeric()
                    ->default(0)
                    ->step('0.01')
                    ->prefix('$')
                    ->label('Opening Balance')
                    ->helperText('Initial balance for this account'),

                TextInput::make('balance')
                    ->numeric()
                    ->disabled()
                    ->dehydrated(false)
                    ->default(0)
                    ->prefix('$')
                    ->label('Current Balance')
                    ->helperText('Updated automatically by posted journal entries'),

                Toggle::make('is_active')
                    ->default(true)
                    ->label('Active'),

                Toggle::make('allow_manual_entry')
                    ->default(true)
                    ->label('Allow Manual Journal Entries')
                    ->helperText('Uncheck for system-controlled accounts'),
            ]);
    }

    #[\Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('account_number')
                    ->sortable()
                    ->searchable()
                    ->label('Number'),
                IndentedTextColumn::make('account_name')
                    ->indentedFromField('parent_id')
                    ->searchable()
                    ->label('Account Name'),
                TextColumn::make('account_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'asset' => 'success',
                        'liability' => 'danger',
                        'equity' => 'info',
                        'revenue' => 'warning',
                        'expense' => 'gray',
                        default => 'gray',
                    })
                    ->label('Type'),
                TextColumn::make('normal_balance')
                    ->badge()
                    ->label('Normal Balance'),
                TextColumn::make('balance')
                    ->money('usd')
                    ->label('Current Balance'),
                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
            ])
            ->defaultSort('account_number')
            ->filters([
                SelectFilter::make('account_type')
                    ->options([
                        'asset' => 'Asset',
                        'liability' => 'Liability',
                        'equity' => 'Equity',
                        'revenue' => 'Revenue',
                        'expense' => 'Expense',
                    ]),
                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        1 => 'Active',
                        0 => 'Inactive',
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

    #[\Override]
    public static function getPages(): array
    {
        return [
            'index' => ListChartOfAccounts::route('/'),
            'create' => CreateChartOfAccounts::route('/create'),
            'edit' => EditChartOfAccounts::route('/{record}/edit'),
        ];
    }
}
