<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Accounts;

use App\Filament\App\Resources\AccountResource\Pages;
use App\Filament\App\Resources\AccountResource\RelationManagers;
use App\Filament\App\Resources\Accounts\Pages\CreateAccount;
use App\Filament\App\Resources\Accounts\Pages\EditAccount;
use App\Filament\App\Resources\Accounts\Pages\ListAccounts;
use App\Models\Account;
use App\Models\Category;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountResource extends Resource
{
    #[\Override]
    protected static ?string $model = Account::class;

    #[\Override]
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    #[\Override]
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->preload(),
                TextInput::make('account_number')
                    ->numeric()
                    ->required(),
                TextInput::make('account_name'),
                TextInput::make('account_type'),
                TextInput::make('balance'),
                Select::make('category_id')
                    ->label('Category')
                    ->options(Category::all()->pluck('name', 'id'))
                    ->searchable(),
            ]);
    }

    #[\Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('account_number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('account_name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('account_type'),
                TextColumn::make('balance'),
                BelongsTo::make('user')
                    ->label('User')
                    ->relationship('user', 'name'),
                TextColumn::make('category.name')
                    ->label('Category')
                    ->searchable()
                    ->sortable(),
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
            'index' => ListAccounts::route('/'),
            'create' => CreateAccount::route('/create'),
            'edit' => EditAccount::route('/{record}/edit'),
        ];
    }
}
