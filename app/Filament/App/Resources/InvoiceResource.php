<?php

namespace App\Filament\App\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Invoice;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\BelongsToSelect;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\App\Resources\InvoiceResource\Pages;
use App\Filament\App\Resources\InvoiceResource\RelationManagers;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(2)->schema([
                    BelongsToSelect::make('customer_id')
                        ->relationship('customer', 'customer_name')
                        ->label('Customer')
                        ->columnSpan(1),
                    DatePicker::make('invoice_date')
                        ->columnSpan(1),
                    DatePicker::make('due_date')
                        ->columnSpan(1),
                    TextInput::make('total_amount')
                        ->numeric()
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set, $get) {
                            if ($get('tax_rate_id')) {
                                $taxRate = TaxRate::find($get('tax_rate_id'));
                                $taxAmount = $state * ($taxRate->rate / 100);
                                $set('tax_amount', $taxAmount);
                            }
                        })
                        ->columnSpan(1),
                    BelongsToSelect::make('tax_rate_id')
                        ->relationship('taxRate', 'name')
                        ->label('Tax Rate')
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set, $get) {
                            if ($state && $get('total_amount')) {
                                $taxRate = TaxRate::find($state);
                                $taxAmount = $get('total_amount') * ($taxRate->rate / 100);
                                $set('tax_amount', $taxAmount);
                            }
                        })
                        ->columnSpan(1),
                    TextInput::make('tax_amount')
                        ->numeric()
                        ->disabled()
                        ->label('Tax Amount')
                        ->columnSpan(1),
                    TextInput::make('late_fee_percentage')
                        ->numeric()
                        ->label('Late Fee (%)')
                        ->default(0)
                        ->columnSpan(1),
                    TextInput::make('grace_period_days')
                        ->numeric()
                        ->label('Grace Period (Days)')
                        ->default(0)
                        ->columnSpan(1),
                    TextInput::make('late_fee_amount')
                        ->disabled()
                        ->numeric()
                        ->label('Late Fee Amount')
                        ->columnSpan(1),
                    Select::make('payment_status')
                        ->options([
                            'pending' => 'Pending',
                            'paid' => 'Paid',
                            'failed' => 'Failed',
                        ])
                        ->label('Payment Status')
                        ->columnSpan(1),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('customer_id')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('invoice_date')
                    ->label('Invoice Date')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('total_amount')
                    ->label('Total Amount')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('payment_status'),
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
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }
}
