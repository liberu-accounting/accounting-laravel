<?php

namespace App\Filament\App\Resources\Bills;

use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\App\Resources\Bills\Pages\ListBills;
use App\Filament\App\Resources\Bills\Pages\CreateBill;
use App\Filament\App\Resources\Bills\Pages\EditBill;
use Filament\Forms;
use Filament\Tables;
use App\Models\Bill;
use App\Models\TaxRate;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Repeater;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;

class BillResource extends Resource
{
    protected static ?string $model = Bill::class;
    
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-receipt-refund';
    
    protected static ?int $navigationSort = 3;
    
    protected static string | \UnitEnum | null $navigationGroup = 'Vendors';
    
    protected static ?string $recordTitleAttribute = 'bill_number';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('vendor_id')
                    ->relationship('vendor', 'vendor_first_name')
                    ->required()
                    ->searchable()
                    ->preload(),
                    
                TextInput::make('bill_number')
                    ->disabled()
                    ->dehydrated(false)
                    ->visible(fn ($record) => $record !== null),
                    
                DatePicker::make('bill_date')
                    ->required()
                    ->default(now()),
                    
                DatePicker::make('due_date')
                    ->required()
                    ->minDate(fn ($get) => $get('bill_date'))
                    ->default(now()->addDays(30)),
                    
                Select::make('purchase_order_id')
                    ->relationship('purchaseOrder', 'po_number')
                    ->searchable()
                    ->preload(),
                    
                TextInput::make('reference_number')
                    ->label('Reference/PO #'),
                    
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
                    ->columns(4)
                    ->defaultItems(1)
                    ->collapsible(),
                    
                TextInput::make('subtotal_amount')
                    ->numeric()
                    ->disabled()
                    ->dehydrated(false),
                    
                TextInput::make('tax_amount')
                    ->numeric()
                    ->disabled()
                    ->dehydrated(false),
                    
                TextInput::make('total_amount')
                    ->numeric()
                    ->disabled()
                    ->dehydrated(false),
                    
                Select::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'open' => 'Open',
                        'paid' => 'Paid',
                        'overdue' => 'Overdue',
                        'void' => 'Void',
                    ])
                    ->default('draft')
                    ->required(),
                    
                Textarea::make('notes')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('bill_number')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('vendor.vendor_first_name')
                    ->label('Vendor')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('bill_date')
                    ->date()
                    ->sortable(),
                    
                TextColumn::make('due_date')
                    ->date()
                    ->sortable(),
                    
                TextColumn::make('total_amount')
                    ->money('USD')
                    ->sortable(),
                    
                TextColumn::make('amount_paid')
                    ->money('USD')
                    ->sortable(),
                    
                TextColumn::make('payment_status')
                    ->badge()
                    ->colors([
                        'danger' => 'unpaid',
                        'warning' => 'partial',
                        'success' => 'paid',
                    ]),
                    
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'secondary' => 'draft',
                        'info' => 'open',
                        'success' => 'paid',
                        'danger' => 'overdue',
                        'warning' => 'void',
                    ]),
                    
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'open' => 'Open',
                        'paid' => 'Paid',
                        'overdue' => 'Overdue',
                        'void' => 'Void',
                    ]),
                SelectFilter::make('payment_status')
                    ->options([
                        'unpaid' => 'Unpaid',
                        'partial' => 'Partial',
                        'paid' => 'Paid',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('bill_date', 'desc');
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
            'index' => ListBills::route('/'),
            'create' => CreateBill::route('/create'),
            'edit' => EditBill::route('/{record}/edit'),
        ];
    }
}
