<?php

namespace App\Filament\App\Resources\VendorCredits;

use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\App\Resources\VendorCredits\Pages\ListVendorCredits;
use App\Filament\App\Resources\VendorCredits\Pages\CreateVendorCredit;
use App\Filament\App\Resources\VendorCredits\Pages\EditVendorCredit;
use Filament\Forms;
use Filament\Tables;
use App\Models\VendorCredit;
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

class VendorCreditResource extends Resource
{
    protected static ?string $model = VendorCredit::class;
    
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-receipt-refund';
    
    protected static ?int $navigationSort = 4;
    
    protected static string | \UnitEnum | null $navigationGroup = 'Vendors';
    
    protected static ?string $recordTitleAttribute = 'vendor_credit_number';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Credit Information')
                    ->schema([
                        Select::make('vendor_id')
                            ->relationship('vendor', 'vendor_first_name')
                            ->required()
                            ->searchable()
                            ->preload(),
                            
                        TextInput::make('vendor_credit_number')
                            ->label('Credit #')
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn ($record) => $record !== null),
                            
                        DatePicker::make('credit_date')
                            ->required()
                            ->default(now()),
                            
                        Select::make('bill_id')
                            ->relationship('bill', 'bill_number')
                            ->label('Original Bill')
                            ->searchable()
                            ->preload(),
                            
                        Select::make('reason')
                            ->options([
                                'product_return' => 'Product Return',
                                'billing_error' => 'Billing Error',
                                'overpayment' => 'Overpayment',
                                'discount' => 'Discount',
                                'other' => 'Other',
                            ]),
                            
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
                            
                        TextInput::make('amount_applied')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false)
                            ->prefix('$'),
                            
                        TextInput::make('amount_remaining')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false)
                            ->prefix('$')
                            ->visible(fn ($record) => $record !== null),
                    ])
                    ->columns(3),
                    
                Section::make('Applications')
                    ->schema([
                        Repeater::make('applications')
                            ->relationship()
                            ->schema([
                                Select::make('bill_id')
                                    ->relationship('bill', 'bill_number')
                                    ->required()
                                    ->searchable()
                                    ->preload(),
                                TextInput::make('amount_applied')
                                    ->numeric()
                                    ->required()
                                    ->prefix('$'),
                                DatePicker::make('application_date')
                                    ->required()
                                    ->default(now()),
                            ])
                            ->columns(3)
                            ->collapsible()
                            ->visible(fn ($record) => $record !== null),
                    ]),
                    
                Section::make('Additional Information')
                    ->schema([
                        Select::make('status')
                            ->options([
                                'open' => 'Open',
                                'partial' => 'Partial',
                                'applied' => 'Applied',
                                'void' => 'Void',
                            ])
                            ->default('open')
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
                TextColumn::make('vendor_credit_number')
                    ->label('Credit #')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('vendor.vendor_first_name')
                    ->label('Vendor')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('credit_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                    
                TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('USD')
                    ->sortable(),
                    
                TextColumn::make('amount_remaining')
                    ->label('Remaining')
                    ->money('USD')
                    ->sortable(),
                    
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'info' => 'open',
                        'warning' => 'partial',
                        'success' => 'applied',
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
                        'open' => 'Open',
                        'partial' => 'Partial',
                        'applied' => 'Applied',
                        'void' => 'Void',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('apply_to_bill')
                    ->label('Apply to Bill')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->amount_remaining > 0 && $record->status !== 'void')
                    ->form([
                        Select::make('bill_id')
                            ->label('Bill')
                            ->relationship('bill', 'bill_number')
                            ->required()
                            ->searchable(),
                        TextInput::make('amount')
                            ->numeric()
                            ->required()
                            ->prefix('$')
                            ->maxValue(fn ($record) => $record->amount_remaining),
                    ])
                    ->requiresConfirmation()
                    ->action(function ($record, array $data) {
                        $record->applyToBill($data['bill_id'], $data['amount']);
                    }),
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
            ->defaultSort('credit_date', 'desc');
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
            'index' => ListVendorCredits::route('/'),
            'create' => CreateVendorCredit::route('/create'),
            'edit' => EditVendorCredit::route('/{record}/edit'),
        ];
    }
}
