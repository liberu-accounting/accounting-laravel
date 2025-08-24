<?php

namespace App\Filament\App\Resources\Companies\Pages;

use App\Filament\App\Resources\Companies\CompanyResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCompany extends CreateRecord
{
    protected static string $resource = CompanyResource::class;
}