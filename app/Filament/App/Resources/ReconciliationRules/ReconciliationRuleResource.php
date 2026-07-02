<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\ReconciliationRules;

use App\Filament\App\Resources\ReconciliationRules\Pages\CreateReconciliationRule;
use App\Filament\App\Resources\ReconciliationRules\Pages\EditReconciliationRule;
use App\Filament\App\Resources\ReconciliationRules\Pages\ListReconciliationRules;
use App\Models\ReconciliationRule;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

// Team scoping + team_id stamping are handled automatically by the App panel's
// Filament tenancy (->tenant(Team::class, ownershipRelationship: 'team')), the
// same as every other team-scoped resource here. Global resources opt out with
// $isScopedToTenant = false; this one is team-scoped, so it uses the default.
class ReconciliationRuleResource extends Resource
{
    #[\Override]
    protected static ?string $model = ReconciliationRule::class;

    #[\Override]
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-adjustments-horizontal';

    #[\Override]
    protected static string|\UnitEnum|null $navigationGroup = 'Banking';

    #[\Override]
    protected static ?int $navigationSort = 3;

    #[\Override]
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                Select::make('match_field')
                    ->label('Match Field')
                    ->options([
                        'description' => 'Description',
                        'amount' => 'Amount',
                        'reference' => 'Reference',
                    ])
                    ->default('description')
                    ->required(),

                Select::make('match_operator')
                    ->label('Operator')
                    ->options([
                        'contains' => 'Contains',
                        'equals' => 'Equals',
                        'between' => 'Between (amount range)',
                    ])
                    ->default('contains')
                    ->required(),

                TextInput::make('match_value')
                    ->label('Value')
                    ->required(),

                TextInput::make('match_value_secondary')
                    ->label('Second Value')
                    ->helperText('Upper bound, used only by the "between" operator'),

                Select::make('action_account_id')
                    ->label('Assign Account')
                    ->relationship('actionAccount', 'account_name')
                    ->searchable()
                    ->preload()
                    ->helperText('Account assigned to matched transactions'),

                TextInput::make('priority')
                    ->numeric()
                    ->default(0)
                    ->helperText('Lower numbers run first'),

                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ]);
    }

    #[\Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('priority')
                    ->sortable(),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('match_field')
                    ->badge(),

                TextColumn::make('match_operator')
                    ->badge(),

                TextColumn::make('match_value')
                    ->label('Value'),

                TextColumn::make('actionAccount.account_name')
                    ->label('Assign Account'),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('priority');
    }

    #[\Override]
    public static function getPages(): array
    {
        return [
            'index' => ListReconciliationRules::route('/'),
            'create' => CreateReconciliationRule::route('/create'),
            'edit' => EditReconciliationRule::route('/{record}/edit'),
        ];
    }
}
