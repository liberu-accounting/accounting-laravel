<?php

namespace App\Filament\App\Resources\SalesReceipts;

use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\App\Resources\SalesReceipts\Pages\ListSalesReceipts;
use App\Filament\App\Resources\SalesReceipts\Pages\CreateSalesReceipt;
use App\Filament\App\Resources\SalesReceipts\Pages\EditSalesReceipt;
use Filament\Forms;
use Filament\Tables;
use App\Models\SalesReceipt;
use App\Models\TaxRate;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;

class SalesReceiptResource extends Resource
{
    protected static ?string $model = SalesReceipt::class;
    
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-receipt-percent';
    
    protected static ?int $navigationSort = 5;
    
    protected static string | \UnitEnum | null $navigationGroup = 'Sales';
    
    protected static ?string $recordTitleAttribute = 'sales_receipt_number';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Receipt Information')
                    ->schema([
                        Select::make('customer_id')
                            ->relationship('customer', 'customer_name')
                            ->required()
                            ->searchable()
                            ->preload(),
                            
                        TextInput::make('sales_receipt_number')
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn ($record) => $record !== null),
                            
                        DatePicker::make('sales_receipt_date')
                            ->label('Receipt Date')
                            ->required()
                            ->default(now()),
                            
                        Select::make('payment_method')
                            ->options([
                                'cash' => 'Cash',
                                'check' => 'Check',
                                'credit_card' => 'Credit Card',
                                'debit_card' => 'Debit Card',
                                'bank_transfer' => 'Bank Transfer',
                                'other' => 'Other',
                            ])
                            ->required()
                            ->default('cash'),
                            
                        TextInput::make('reference_number')
                            ->label('Reference #'),
                            
                        Select::make('deposit_to_account_id')
                            ->relationship('depositAccount', 'name')
                            ->label('Deposit To')
                            ->searchable()
                            ->preload(),
                            
                        Select::make('tax_rate_id')
                            ->relationship('taxRate', 'name')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                if ($state && $get('subtotal_amount')) {
                                    $taxRate = TaxRate::find($state);
                                    $taxAmount = $get('subtotal_amount') * ($taxRate->rate / 100);
                                    $set('tax_amount', $taxAmount);
                                }
                            }),
                    ])
                    ->columns(2),
                    
                Section::make('Line Items')
                    ->schema([
                        Repeater::make('items')
                            ->relationship()
                            ->schema([
                                Select::make('account_id')
                                    ->relationship('account', 'name')
                                    ->searchable()
                                    ->preload(),
                                TextInput::make('description')
                                    ->required()
                                    ->columnSpan(2),
                                TextInput::make('quantity')
                                    ->numeric()
                                    ->default(1)
                                    ->required()
                                    ->reactive(),
                                TextInput::make('unit_price')
                                    ->numeric()
                                    ->required()
                                    ->reactive(),
                            ])
                            ->columns(5)
                            ->defaultItems(1)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['description'] ?? null),
                    ]),
                    
                Section::make('Totals')
                    ->schema([
                        TextInput::make('subtotal_amount')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false)
                            ->prefix('$'),
                            
                        TextInput::make('tax_amount')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false)
                            ->prefix('$'),
                            
                        TextInput::make('total_amount')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false)
                            ->prefix('$'),
                    ])
                    ->columns(3),
                    
                Section::make('Additional Information')
                    ->schema([
                        Select::make('status')
                            ->options([
                                'completed' => 'Completed',
                                'void' => 'Void',
                            ])
                            ->default('completed')
                            ->required(),
                            
                        Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sales_receipt_number')
                    ->label('Receipt #')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('customer.customer_name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('sales_receipt_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                    
                TextColumn::make('payment_method')
                    ->label('Payment Method')
                    ->formatStateUsing(fn (string $state): string => str_replace('_', ' ', ucwords($state, '_')))
                    ->sortable(),
                    
                TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('USD')
                    ->sortable(),
                    
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'success' => 'completed',
                        'danger' => 'void',
                    ]),
                    
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'completed' => 'Completed',
                        'void' => 'Void',
                    ]),
                SelectFilter::make('payment_method')
                    ->options([
                        'cash' => 'Cash',
                        'check' => 'Check',
                        'credit_card' => 'Credit Card',
                        'debit_card' => 'Debit Card',
                        'bank_transfer' => 'Bank Transfer',
                        'other' => 'Other',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('void')
                    ->label('Void')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => $record->status !== 'void')
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->void()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('sales_receipt_date', 'desc');
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
            'index' => ListSalesReceipts::route('/'),
            'create' => CreateSalesReceipt::route('/create'),
            'edit' => EditSalesReceipt::route('/{record}/edit'),
        ];
    }
}
