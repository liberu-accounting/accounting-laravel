<?php

namespace App\Filament\App\Resources;

use Filament\Forms\Form;
use App\Models\TaxRate;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use App\Filament\App\Resources\InvoiceResource\Pages\ListInvoices;
use App\Filament\App\Resources\InvoiceResource\Pages\CreateInvoice;
use App\Filament\App\Resources\InvoiceResource\Pages\EditInvoice;
use Filament\Forms;
use Filament\Tables;
use App\Models\Invoice;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\App\Resources\InvoiceResource\Pages;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    public static function form(Form $form): Form
    {
        return $form
            ->components([
                Select::make('customer_id')
                    ->relationship('customer', 'customer_name')
                    ->required()
                    ->searchable(),
                TextInput::make('invoice_number')
                    ->disabled()
                    ->dehydrated(false)
                    ->visible(fn ($record) => $record !== null),
                DatePicker::make('invoice_date')
                    ->required(),
                DatePicker::make('due_date'),
                TextInput::make('total_amount')
                    ->numeric()
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set, $get) {
                        if ($get('tax_rate_id')) {
                            $taxRate = TaxRate::find($get('tax_rate_id'));
                            $taxAmount = $state * ($taxRate->rate / 100);
                            $set('tax_amount', $taxAmount);
                        }
                    }),
                Select::make('tax_rate_id')
                    ->relationship('taxRate', 'name')
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set, $get) {
                        if ($state && $get('total_amount')) {
                            $taxRate = TaxRate::find($state);
                            $taxAmount = $get('total_amount') * ($taxRate->rate / 100);
                            $set('tax_amount', $taxAmount);
                        }
                    }),
                TextInput::make('tax_amount')
                    ->numeric()
                    ->disabled(),
                Select::make('payment_status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'failed' => 'Failed',
                    ])
                    ->required(),
                Textarea::make('notes')
                    ->rows(3),
                Toggle::make('is_recurring')
                    ->label('Recurring Invoice')
                    ->reactive(),
                Select::make('recurrence_frequency')
                    ->options([
                        'daily' => 'Daily',
                        'weekly' => 'Weekly',
                        'monthly' => 'Monthly',
                        'yearly' => 'Yearly',
                    ])
                    ->visible(fn ($get) => $get('is_recurring'))
                    ->required(fn ($get) => $get('is_recurring')),
                DatePicker::make('recurrence_start')
                    ->label('Start Date')
                    ->visible(fn ($get) => $get('is_recurring'))
                    ->required(fn ($get) => $get('is_recurring')),
                DatePicker::make('recurrence_end')
                    ->label('End Date')
                    ->visible(fn ($get) => $get('is_recurring'))
                    ->minDate(fn ($get) => $get('recurrence_start')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer.customer_name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('invoice_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('total_amount')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('payment_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'failed' => 'danger',
                        default => 'warning',
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('download')
                    ->icon('heroicon-o-document-download')
                    ->action(fn (Invoice $record) => response()->streamDownload(
                        fn () => print($record->generatePDF()),
                        "invoice_{$record->invoice_number}.pdf"
                    )),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInvoices::route('/'),
            'create' => CreateInvoice::route('/create'),
            'edit' => EditInvoice::route('/{record}/edit'),
        ];
    }
}
