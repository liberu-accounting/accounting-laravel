<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Accounts\Pages;

use App\Filament\App\Resources\Accounts\AccountResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateAccount extends CreateRecord
{
    #[\Override]
    protected static string $resource = AccountResource::class;
}
