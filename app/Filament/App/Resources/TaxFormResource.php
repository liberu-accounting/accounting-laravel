<?php

namespace App\Filament\App\Resources;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use App\Filament\App\Resources\TaxFormResource\Pages\ListTaxForms;
use App\Filament\App\Resources\TaxFormResource\Pages\CreateTaxForm;
use App\Filament\App\Resources\TaxFormResource\Pages\EditTaxForm;
use App\Models\TaxForm;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use App\Filament\App\Resources\TaxFormResource\Pages;

class TaxFormResource extends Resource
{
    protected static ?string $model = TaxForm::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-text';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('form_type')
                    ->options([
                        '1099-MISC' => '1099-MISC',
                        '1099-NEC' => '1099-NEC',
                    ])
                    ->required(),
                Select::make('customer_id')
                    ->relationship('customer', 'customer_name')
                    ->required(),
                TextInput::make('tax_year')
                    ->required()
                    ->numeric()
                    ->minValue(2000)
                    ->maxValue(date('Y')),
                TextInput::make('total_payments')
                    ->required()
                    ->numeric()
                    ->disabled(),
                TextInput::make('total_tax_withheld')
                    ->required()
                    ->numeric()
                    ->disabled(),
                Select::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'generated' => 'Generated',
                        'submitted' => 'Submitted',
                    ])
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('form_type'),
                TextColumn::make('customer.customer_name'),
                TextColumn::make('tax_year'),
                TextColumn::make('total_payments')
                    ->money('USD'),
                TextColumn::make('status'),
            ])
            ->filters([
                SelectFilter::make('form_type'),
                SelectFilter::make('status'),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('generate_pdf')
                    ->label('Generate PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->action(fn (TaxForm $record) => $record->generatePDF()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTaxForms::route('/'),
            'create' => CreateTaxForm::route('/create'),
            'edit' => EditTaxForm::route('/{record}/edit'),
        ];
    }
}
