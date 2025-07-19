<?php

namespace App\Filament\App\Resources;

use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\App\Resources\TimeEntryResource\Pages\ListTimeEntries;
use App\Filament\App\Resources\TimeEntryResource\Pages\CreateTimeEntry;
use App\Filament\App\Resources\TimeEntryResource\Pages\EditTimeEntry;
use App\Filament\App\Resources\TimeEntryResource\Pages;
use App\Models\TimeEntry;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TimeEntryResource extends Resource
{
    protected static ?string $model = TimeEntry::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    public static function form(Form $form): Form
    {
        return $form
            ->components([
                Select::make('customer_id')
                    ->relationship('customer', 'customer_name')
                    ->required(),
                Select::make('invoice_id')
                    ->relationship('invoice', 'invoice_id')
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
                    ->reactive()
                    ->afterStateUpdated(fn ($state, callable $set, TimeEntry $record) => 
                        $set('total_amount', $record->calculateTotalAmount())
                    ),
                TextInput::make('total_amount')
                    ->numeric()
                    ->disabled(),
            ]);
    }

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

    public static function getPages(): array
    {
        return [
            'index' => ListTimeEntries::route('/'),
            'create' => CreateTimeEntry::route('/create'),
            'edit' => EditTimeEntry::route('/{record}/edit'),
        ];
    }
}
