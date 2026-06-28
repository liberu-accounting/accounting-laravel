<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\InventoryItems;

use App\Filament\App\Resources\InventoryItems\Pages\CreateInventoryItem;
use App\Filament\App\Resources\InventoryItems\Pages\EditInventoryItem;
use App\Filament\App\Resources\InventoryItems\Pages\ListInventoryItems;
use App\Models\Account;
use App\Models\InventoryItem;
use App\Modules\Inventory\InventoryModule;
use App\Services\InventoryMovementService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InventoryItemResource extends Resource
{
    #[\Override]
    protected static ?string $model = InventoryItem::class;

    // Gated by the Inventory module: disabling the module removes this resource
    // (access + navigation) without touching its code or data.
    #[\Override]
    public static function canAccess(): bool
    {
        return InventoryModule::isActive();
    }

    #[\Override]
    public static function shouldRegisterNavigation(): bool
    {
        return InventoryModule::isActive();
    }

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
                Action::make('recordPurchase')
                    ->label('Record purchase')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->schema([
                        TextInput::make('quantity')->numeric()->minValue(1)->required(),
                        TextInput::make('unit_cost')->numeric()->minValue(0)->required(),
                    ])
                    ->action(function (InventoryItem $record, array $data): void {
                        app(InventoryMovementService::class)
                            ->recordPurchase($record, (int) $data['quantity'], (float) $data['unit_cost']);

                        Notification::make()->title('Purchase recorded')->success()->send();
                    }),
                Action::make('recordSale')
                    ->label('Record sale')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('warning')
                    ->schema([
                        TextInput::make('quantity')
                            ->numeric()->minValue(1)
                            ->maxValue(fn (InventoryItem $record): int => $record->current_quantity)
                            ->required(),
                        Select::make('cogs_account_id')
                            ->label('COGS expense account')
                            ->options(fn () => Account::where('account_type', 'expense')->pluck('account_name', 'id'))
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (InventoryItem $record, array $data): void {
                        $cogsAccount = Account::findOrFail($data['cogs_account_id']);
                        $result = app(InventoryMovementService::class)
                            ->recordSale($record, (int) $data['quantity'], $cogsAccount);

                        Notification::make()
                            ->title('Sale recorded')
                            ->body('COGS posted: '.number_format($result['cogs'], 2))
                            ->success()
                            ->send();
                    }),
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
