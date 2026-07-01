<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\ApprovalRules;

use App\Filament\App\Resources\ApprovalRules\Pages\CreateApprovalRule;
use App\Filament\App\Resources\ApprovalRules\Pages\EditApprovalRule;
use App\Filament\App\Resources\ApprovalRules\Pages\ListApprovalRules;
use App\Models\ApprovalRule;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role;

class ApprovalRuleResource extends Resource
{
    #[\Override]
    protected static ?string $model = ApprovalRule::class;

    // ApprovalRule has no `team()` relation (see App\Models\ApprovalRule), so
    // Filament's automatic tenant scope/stamp — which requires that relation —
    // can't apply here. Scope + stamp team_id manually below instead.
    protected static bool $isScopedToTenant = false;

    #[\Override]
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    #[\Override]
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('team_id', Filament::getTenant()?->getKey());
    }

    /**
     * @return array<string, string>
     */
    private static function approvableTypeOptions(): array
    {
        // Values must match `class_basename($this)` in App\Concerns\Approvable::submitForApproval,
        // which is what ApprovalRule::matchFor() compares against — not the morph class.
        return [
            'Invoice' => 'Invoice',
            'Bill' => 'Bill',
            'Expense' => 'Expense',
            'JournalEntry' => 'Journal Entry',
        ];
    }

    #[\Override]
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('approvable_type')
                    ->options(self::approvableTypeOptions())
                    ->required(),
                TextInput::make('min_amount')
                    ->numeric()
                    ->default(0)
                    ->required(),
                Select::make('steps')
                    ->label('Approval steps (in order)')
                    ->options(Role::pluck('name', 'name'))
                    ->multiple()
                    ->required(),
                TextInput::make('deadline_days')
                    ->numeric()
                    ->nullable(),
                Select::make('fallback_role')
                    ->options(Role::pluck('name', 'name'))
                    ->nullable(),
                Toggle::make('is_active')
                    ->default(true),
            ]);
    }

    #[\Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('approvable_type')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('min_amount')
                    ->money()
                    ->sortable(),
                TextColumn::make('deadline_days')
                    ->sortable(),
                TextColumn::make('fallback_role')
                    ->searchable(),
                IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    #[\Override]
    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    #[\Override]
    public static function getPages(): array
    {
        return [
            'index' => ListApprovalRules::route('/'),
            'create' => CreateApprovalRule::route('/create'),
            'edit' => EditApprovalRule::route('/{record}/edit'),
        ];
    }
}
