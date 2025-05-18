<?php

namespace App\Filament\App\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Company;
use Filament\Forms\Form;
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
            ->schema([
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
            'index' => Pages\ListCompanies::route('/'),
            'create' => Pages\CreateCompany::route('/create'),
            'edit' => Pages\EditCompany::route('/{record}/edit'),
        ];
    }
}
