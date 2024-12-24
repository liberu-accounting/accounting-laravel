

<?php

namespace App\Filament\App\Resources;

use App\Models\TaxForm;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;

class TaxFormResource extends Resource
{
    protected static ?string $model = TaxForm::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('form_type')
                    ->options([
                        '1099-MISC' => '1099-MISC',
                        '1099-NEC' => '1099-NEC',
                    ])
                    ->required(),
                Forms\Components\Select::make('customer_id')
                    ->relationship('customer', 'customer_name')
                    ->required(),
                Forms\Components\TextInput::make('tax_year')
                    ->required()
                    ->numeric()
                    ->minValue(2000)
                    ->maxValue(date('Y')),
                Forms\Components\TextInput::make('total_payments')
                    ->required()
                    ->numeric()
                    ->disabled(),
                Forms\Components\TextInput::make('total_tax_withheld')
                    ->required()
                    ->numeric()
                    ->disabled(),
                Forms\Components\Select::make('status')
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
                Tables\Columns\TextColumn::make('form_type'),
                Tables\Columns\TextColumn::make('customer.customer_name'),
                Tables\Columns\TextColumn::make('tax_year'),
                Tables\Columns\TextColumn::make('total_payments')
                    ->money('USD'),
                Tables\Columns\TextColumn::make('status'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('form_type'),
                Tables\Filters\SelectFilter::make('status'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('generate_pdf')
                    ->label('Generate PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->action(fn (TaxForm $record) => $record->generatePDF()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTaxForms::route('/'),
            'create' => Pages\CreateTaxForm::route('/create'),
            'edit' => Pages\EditTaxForm::route('/{record}/edit'),
        ];
    }
}