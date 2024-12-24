

<?php

namespace App\Filament\App\Resources;

use App\Models\ReminderSetting;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class ReminderSettingResource extends Resource
{
    protected static ?string $model = ReminderSetting::class;
    protected static ?string $navigationIcon = 'heroicon-o-bell';
    protected static ?string $navigationLabel = 'Reminder Settings';
    protected static ?string $navigationGroup = 'Settings';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('days_before_reminder')
                    ->label('Days Before First Reminder')
                    ->numeric()
                    ->required()
                    ->min(1)
                    ->helperText('Number of days after invoice date before sending first reminder'),
                
                TextInput::make('reminder_frequency_days')
                    ->label('Days Between Reminders')
                    ->numeric()
                    ->required()
                    ->min(1)
                    ->helperText('Number of days to wait between reminders'),
                
                TextInput::make('max_reminders')
                    ->label('Maximum Number of Reminders')
                    ->numeric()
                    ->required()
                    ->min(1)
                    ->helperText('Maximum number of reminders to send per invoice'),
                
                Toggle::make('is_active')
                    ->label('Enable Reminders')
                    ->required()
                    ->helperText('Toggle the reminder system on/off'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('days_before_reminder')
                    ->label('Days Before First Reminder')
                    ->sortable(),
                TextColumn::make('reminder_frequency_days')
                    ->label('Reminder Frequency (Days)')
                    ->sortable(),
                TextColumn::make('max_reminders')
                    ->label('Max Reminders')
                    ->sortable(),
                ToggleColumn::make('is_active')
                    ->label('Active'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRecords::route('/'),
            'edit' => EditRecord::route('/{record}/edit'),
        ];
    }
}