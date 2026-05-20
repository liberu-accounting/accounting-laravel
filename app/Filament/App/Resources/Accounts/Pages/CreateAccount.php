<?php

namespace App\Filament\App\Resources\Accounts\Pages;

use App\Filament\App\Resources\Accounts\AccountResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateAccount extends CreateRecord
{
    protected static string $resource = AccountResource::class;
}
