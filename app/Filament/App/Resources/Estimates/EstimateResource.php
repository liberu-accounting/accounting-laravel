<?php

namespace App\Filament\App\Resources\Estimates;

use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\Action;
use App\Filament\App\Resources\Estimates\Pages\ListEstimates;
use App\Filament\App\Resources\Estimates\Pages\CreateEstimate;
use App\Filament\App\Resources\Estimates\Pages\EditEstimate;
use Filament\Forms;
use Filament\Tables;
use App\Models\Estimate;
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

class EstimateResource extends Resource
{
    protected static ?string $model = Estimate::class;
    
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-duplicate';
    
    protected static ?int $navigationSort = 2;
    
    protected static string | \UnitEnum | null $navigationGroup = 'Sales';
    
    protected static ?string $recordTitleAttribute = 'estimate_number';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('customer_id')
                    ->relationship('customer', 'customer_name')
                    ->required()
                    ->searchable()
                    ->preload(),
                    
                TextInput::make('estimate_number')
                    ->disabled()
                    ->dehydrated(false)
                    ->visible(fn ($record) => $record !== null),
                    
                DatePicker::make('estimate_date')
                    ->required()
                    ->default(now()),
                    
                DatePicker::make('expiration_date')
                    ->minDate(fn ($get) => $get('estimate_date'))
                    ->default(now()->addDays(30)),
                    
                Select::make('tax_rate_id')
                    ->relationship('taxRate', 'name')
                    ->reactive(),
                    
                Repeater::make('items')
                    ->relationship()
                    ->schema([
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
                        'sent' => 'Sent',
                        'viewed' => 'Viewed',
                        'accepted' => 'Accepted',
                        'declined' => 'Declined',
                        'expired' => 'Expired',
                    ])
                    ->default('draft')
                    ->required(),
                    
                Textarea::make('notes')
                    ->rows(3)
                    ->columnSpanFull(),
                    
                Textarea::make('terms')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('estimate_number')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('customer.customer_name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('estimate_date')
                    ->date()
                    ->sortable(),
                    
                TextColumn::make('expiration_date')
                    ->date()
                    ->sortable(),
                    
                TextColumn::make('total_amount')
                    ->money('USD')
                    ->sortable(),
                    
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'secondary' => 'draft',
                        'info' => 'sent',
                        'primary' => 'viewed',
                        'success' => 'accepted',
                        'danger' => 'declined',
                        'warning' => 'expired',
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
                        'sent' => 'Sent',
                        'viewed' => 'Viewed',
                        'accepted' => 'Accepted',
                        'declined' => 'Declined',
                        'expired' => 'Expired',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('convert_to_invoice')
                    ->label('Convert to Invoice')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->visible(fn ($record) => $record->status === 'accepted' && !$record->invoice_id)
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->convertToInvoice()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('estimate_date', 'desc');
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
            'index' => ListEstimates::route('/'),
            'create' => CreateEstimate::route('/create'),
            'edit' => EditEstimate::route('/{record}/edit'),
        ];
    }
}
