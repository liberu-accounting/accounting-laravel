<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\InventoryItems;

use App\Filament\App\Resources\InventoryItems\Pages\CreateInventoryItem;
use App\Filament\App\Resources\InventoryItems\Pages\EditInventoryItem;
use App\Filament\App\Resources\InventoryItems\Pages\ListInventoryItems;
use App\Models\InventoryItem;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InventoryItemResource extends Resource
{
    #[\Override]
    protected static ?string $model = InventoryItem::class;

    #[\Override]
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    #[\Override]
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('sku')
                    ->label('SKU')
                    ->required()
                    ->unique(ignoreRecord: true),
                Textarea::make('description')
                    ->rows(2)
                    ->columnSpanFull(),
                TextInput::make('unit_price')
                    ->numeric()
                    ->required(),
                TextInput::make('current_quantity')
                    ->numeric()
                    ->default(0)
                    ->required(),
                TextInput::make('reorder_point')
                    ->numeric()
                    ->default(0)
                    ->required(),
                Select::make('valuation_method')
                    ->options([
                        'fifo' => 'FIFO',
                        'lifo' => 'LIFO',
                        'average' => 'Average cost',
                    ])
                    ->default('fifo')
                    ->required(),
                Select::make('account_id')
                    ->label('Inventory account')
                    ->relationship('account', 'account_name')
                    ->searchable()
                    ->required(),
                Toggle::make('is_active')
                    ->default(true),
            ]);
    }

    #[\Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable(),
                TextColumn::make('current_quantity')
                    ->label('Qty')
                    ->sortable(),
                TextColumn::make('unit_price')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('valuation_method')
                    ->badge(),
                IconColumn::make('is_active')
                    ->boolean(),
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

    #[\Override]
    public static function getPages(): array
    {
        return [
            'index' => ListInventoryItems::route('/'),
            'create' => CreateInventoryItem::route('/create'),
            'edit' => EditInventoryItem::route('/{record}/edit'),
        ];
    }
}
