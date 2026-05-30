<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Companies;

use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\App\Resources\Companies\Pages\ListCompanies;
use App\Filament\App\Resources\Companies\Pages\CreateCompany;
use App\Filament\App\Resources\Companies\Pages\EditCompany;
use Filament\Forms;
use Filament\Tables;
use App\Models\Company;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\ImageColumn;
use App\Filament\App\Resources\CompanyResource\Pages;

class CompanyResource extends Resource
{
    #[\Override]
    protected static ?string $model = Company::class;
    #[\Override]
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-building-office';
    #[\Override]
    protected static ?int $navigationSort = 3;
    #[\Override]
    protected static string | \UnitEnum | null $navigationGroup = 'Settings';

    #[\Override]
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('company_name')
                    ->required(),
                TextInput::make('company_address')
                    ->required(),
                TextInput::make('company_email')
                    ->email()
                    ->required(),
                TextInput::make('company_phone')
                    ->tel()
                    ->required(),
                TextInput::make('company_city')
                    ->required(),
                TextInput::make('company_tin')
                    ->required(),
                FileUpload::make('company_logo')
                    ->image()
                    ->directory('company-logos')
                    ->visibility('public'),
            ]);
    }

    #[\Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company_name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('company_address')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('company_email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('company_phone')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('company_city')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('company_tin')
                    ->searchable()
                    ->sortable(),
                ImageColumn::make('company_logo'),
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
            'index' => ListCompanies::route('/'),
            'create' => CreateCompany::route('/create'),
            'edit' => EditCompany::route('/{record}/edit'),
        ];
    }
}
