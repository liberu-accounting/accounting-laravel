<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Categories\Pages;

use App\Filament\App\Resources\Categories\CategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCategory extends CreateRecord
{
    #[\Override]
    protected static string $resource = CategoryResource::class;
}
