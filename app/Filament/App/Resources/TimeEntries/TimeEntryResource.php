<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\TimeEntries;

use App\Filament\App\Resources\TimeEntries\Pages\CreateTimeEntry;
use App\Filament\App\Resources\TimeEntries\Pages\EditTimeEntry;
use App\Filament\App\Resources\TimeEntries\Pages\ListTimeEntries;
use App\Filament\App\Resources\TimeEntryResource\Pages;
use App\Models\TimeEntry;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TimeEntryResource extends Resource
{
    #[\Override]
    protected static ?string $model = TimeEntry::class;

    #[\Override]
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clock';

    #[\Override]
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('customer_id')
                    ->relationship('customer', 'customer_name')
                    ->required(),
                Select::make('invoice_id')
                    ->relationship('invoice', 'id')
                    ->nullable(),
                DateTimePicker::make('start_time')
                    ->required(),
                DateTimePicker::make('end_time')
                    ->required(),
                TextInput::make('description')
                    ->required(),
                TextInput::make('hourly_rate')
                    ->numeric()
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn ($state, callable $set, TimeEntry $record) =>
                        $set('total_amount', $record->calculateTotalAmount())
                    ),
                TextInput::make('total_amount')
                    ->numeric()
                    ->disabled(),
            ]);
    }

    #[\Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('customer.customer_name'),
                TextColumn::make('start_time'),
                TextColumn::make('end_time'),
                TextColumn::make('description'),
                TextColumn::make('hourly_rate'),
                TextColumn::make('total_amount'),
                TextColumn::make('invoice_id'),
            ])
            ->filters([
                //
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
            'index' => ListTimeEntries::route('/'),
            'create' => CreateTimeEntry::route('/create'),
            'edit' => EditTimeEntry::route('/{record}/edit'),
        ];
    }
}
