<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\PayrollResource\Pages\ListPayrolls;
use App\Filament\Resources\PayrollResource\Pages\CreatePayroll;
use App\Filament\Resources\PayrollResource\Pages\EditPayroll;
use App\Filament\Resources\PayrollResource\Pages;
use App\Models\Payroll;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PayrollResource extends Resource
{
    protected static ?string $model = Payroll::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-currency-dollar';
    protected static string | \UnitEnum | null $navigationGroup = 'HR';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('employee_id')
                    ->relationship('employee', 'name')
                    ->required(),
                TextInput::make('base_salary')
                    ->required()
                    ->numeric()
                    ->prefix('$'),
                TextInput::make('overtime_hours')
                    ->numeric()
                    ->default(0),
                TextInput::make('overtime_rate')
                    ->numeric()
                    ->prefix('$')
                    ->default(0),
                TextInput::make('other_deductions')
                    ->numeric()
                    ->prefix('$')
                    ->default(0),
                DatePicker::make('pay_period_start')
                    ->required(),
                DatePicker::make('pay_period_end')
                    ->required(),
                DatePicker::make('payment_date')
                    ->required(),
                Select::make('payment_status')
                    ->options([
                        'pending' => 'Pending',
                        'processed' => 'Processed',
                        'paid' => 'Paid',
                    ])
                    ->default('pending')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('base_salary')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('net_salary')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('payment_date')
                    ->date()
                    ->sortable(),
                BadgeColumn::make('payment_status')
                    ->colors([
                        'warning' => 'pending',
                        'primary' => 'processed',
                        'success' => 'paid',
                    ]),
            ])
            ->filters([
                SelectFilter::make('payment_status')
                    ->options([
                        'pending' => 'Pending',
                        'processed' => 'Processed',
                        'paid' => 'Paid',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPayrolls::route('/'),
            'create' => CreatePayroll::route('/create'),
            'edit' => EditPayroll::route('/{record}/edit'),
        ];
    }
}
