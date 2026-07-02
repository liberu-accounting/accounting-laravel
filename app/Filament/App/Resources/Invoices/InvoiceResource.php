<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Invoices;

use App\Filament\App\Resources\Invoices\Pages\CreateInvoice;
use App\Filament\App\Resources\Invoices\Pages\EditInvoice;
use App\Filament\App\Resources\Invoices\Pages\ListInvoices;
use App\Models\Invoice;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InvoiceResource extends Resource
{
    #[\Override]
    protected static ?string $model = Invoice::class;

    #[\Override]
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    #[\Override]
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('customer_id')
                    ->relationship('customer', 'customer_name')
                    ->required()
                    ->searchable(),
                TextInput::make('invoice_number')
                    ->disabled()
                    ->dehydrated(false)
                    ->visible(fn ($record): bool => $record !== null),
                DatePicker::make('invoice_date')
                    ->required(),
                DatePicker::make('due_date'),
                // ponytail: invoice-level tax fields removed — tax lives per-line on
                // invoice_items (see the "items" repeater below), not on the invoice.
                TextInput::make('total_amount')
                    ->numeric()
                    ->required(),
                Select::make('payment_status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'failed' => 'Failed',
                    ])
                    ->required(),
                Repeater::make('items')
                    ->relationship('items')
                    ->label('Line items')
                    ->schema([
                        TextInput::make('description')
                            ->required(),
                        Select::make('account_id')
                            ->relationship('account', 'account_name')
                            ->label('Revenue account')
                            ->searchable(),
                        TextInput::make('quantity')
                            ->numeric()
                            ->default(1)
                            ->required(),
                        TextInput::make('unit_price')
                            ->numeric()
                            ->required(),
                    ])
                    ->columns(4)
                    ->defaultItems(1)
                    ->columnSpanFull(),
                Textarea::make('notes')
                    ->rows(3),
                Toggle::make('is_recurring')
                    ->label('Recurring Invoice')
                    ->live(),
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

    #[\Override]
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
                        fn (): int => print ($record->generatePDF()),
                        "invoice_{$record->invoice_number}.pdf"
                    )),
            ]);
    }

    #[\Override]
    public static function getRelations(): array
    {
        return [];
    }

    #[\Override]
    public static function getPages(): array
    {
        return [
            'index' => ListInvoices::route('/'),
            'create' => CreateInvoice::route('/create'),
            'edit' => EditInvoice::route('/{record}/edit'),
        ];
    }
}
