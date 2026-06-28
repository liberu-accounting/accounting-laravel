<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Payrolls;

use App\Filament\Admin\Resources\Payrolls\Pages\CreatePayroll;
use App\Filament\Admin\Resources\Payrolls\Pages\EditPayroll;
use App\Filament\Admin\Resources\Payrolls\Pages\ListPayrolls;
use App\Models\Payroll;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PayrollResource extends Resource
{
    #[\Override]
    protected static ?string $model = Payroll::class;

    #[\Override]
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-currency-dollar';

    #[\Override]
    protected static string|\UnitEnum|null $navigationGroup = 'HR';

    #[\Override]
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

    #[\Override]
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

    #[\Override]
    public static function getPages(): array
    {
        return [
            'index' => ListPayrolls::route('/'),
            'create' => CreatePayroll::route('/create'),
            'edit' => EditPayroll::route('/{record}/edit'),
        ];
    }
}
