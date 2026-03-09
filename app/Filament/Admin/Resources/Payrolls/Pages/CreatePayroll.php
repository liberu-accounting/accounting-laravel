<?php

namespace App\Filament\Admin\Resources\Payrolls\Pages;

use App\Filament\Admin\Resources\Payrolls\PayrollResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePayroll extends CreateRecord
{
    protected static string $resource = PayrollResource::class;
}
