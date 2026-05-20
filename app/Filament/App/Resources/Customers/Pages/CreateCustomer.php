<?php

namespace App\Filament\App\Resources\Customers\Pages;

use App\Filament\App\Resources\Customers\CustomerResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;
}