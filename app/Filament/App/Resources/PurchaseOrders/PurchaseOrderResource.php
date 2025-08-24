<?php

namespace App\Filament\App\Resources;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\App\Resources\PurchaseOrderResource\Pages\ListPurchaseOrders;
use App\Filament\App\Resources\PurchaseOrderResource\Pages\CreatePurchaseOrder;
use App\Filament\App\Resources\PurchaseOrderResource\Pages\EditPurchaseOrder;
use App\Filament\App\Resources\PurchaseOrderResource\Pages;
use App\Models\PurchaseOrder;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-shopping-cart';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('supplier_id')
                    ->relationship('supplier', 'supplier_first_name')
                    ->required(),
                TextInput::make('po_number')
                    ->default(fn () => PurchaseOrder::generatePoNumber())
                    ->disabled()
                    ->required(),
                DatePicker::make('order_date')
                    ->required(),
                DatePicker::make('expected_delivery_date'),
                Select::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'sent' => 'Sent',
                        'received' => 'Received',
                        'cancelled' => 'Cancelled',
                    ])
                    ->required(),
                Repeater::make('items')
                    ->relationship()
                    ->schema([
                        TextInput::make('description')
                            ->required(),
                        TextInput::make('quantity')
                            ->numeric()
                            ->required(),
                        TextInput::make('unit_price')
                            ->numeric()
                            ->required(),
                    ])
                    ->columns(3),
                Textarea::make('notes'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('po_number')
                    ->searchable(),
                TextColumn::make('supplier.supplier_first_name')
                    ->searchable(),
                TextColumn::make('order_date')
                    ->date(),
                TextColumn::make('total_amount')
                    ->money(),
                BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'draft',
                        'primary' => 'sent',
                        'success' => 'received',
                        'danger' => 'cancelled',
                    ]),
            ])
            ->filters([
                SelectFilter::make('status'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPurchaseOrders::route('/'),
            'create' => CreatePurchaseOrder::route('/create'),
            'edit' => EditPurchaseOrder::route('/{record}/edit'),
        ];
    }
}
