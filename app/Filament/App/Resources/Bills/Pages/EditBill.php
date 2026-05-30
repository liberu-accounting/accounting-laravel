<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Bills\Pages;

use App\Filament\App\Resources\Bills\BillResource;
use Filament\Resources\Pages\EditRecord;

class EditBill extends EditRecord
{
    #[\Override]
    protected static string $resource = BillResource::class;
}
