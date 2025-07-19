<?php

namespace App\Filament\App\Resources;

use Filament\Forms\Form;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\App\Resources\CompanyResource\Pages\ListCompanies;
use App\Filament\App\Resources\CompanyResource\Pages\CreateCompany;
use App\Filament\App\Resources\CompanyResource\Pages\EditCompany;
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
    protected static ?string $model = Company::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationGroup = 'Settings';

    public static function form(Form $form): Form
    {
        return $form
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCompanies::route('/'),
            'create' => CreateCompany::route('/create'),
            'edit' => EditCompany::route('/{record}/edit'),
        ];
    }
}
