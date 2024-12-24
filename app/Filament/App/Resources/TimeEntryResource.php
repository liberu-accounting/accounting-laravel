

<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\TimeEntryResource\Pages;
use App\Models\TimeEntry;
use Filament\Forms;
use Filament\Forms\Form;
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
            ->schema([
                Forms\Components\BelongsToSelect::make('customer_id')
                    ->relationship('customer', 'customer_name')
                    ->required(),
                Forms\Components\BelongsToSelect::make('invoice_id')
                    ->relationship('invoice', 'invoice_id')
                    ->nullable(),
                Forms\Components\DateTimePicker::make('start_time')
                    ->required(),
                Forms\Components\DateTimePicker::make('end_time')
                    ->required(),
                Forms\Components\TextInput::make('description')
                    ->required(),
                Forms\Components\TextInput::make('hourly_rate')
                    ->numeric()
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(fn ($state, callable $set, TimeEntry $record) => 
                        $set('total_amount', $record->calculateTotalAmount())
                    ),
                Forms\Components\TextInput::make('total_amount')
                    ->numeric()
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('customer.customer_name'),
                Tables\Columns\TextColumn::make('start_time'),
                Tables\Columns\TextColumn::make('end_time'),
                Tables\Columns\TextColumn::make('description'),
                Tables\Columns\TextColumn::make('hourly_rate'),
                Tables\Columns\TextColumn::make('total_amount'),
                Tables\Columns\TextColumn::make('invoice_id'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTimeEntries::route('/'),
            'create' => Pages\CreateTimeEntry::route('/create'),
            'edit' => Pages\EditTimeEntry::route('/{record}/edit'),
        ];
    }
}