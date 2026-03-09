<?php

namespace App\Filament\Admin\Resources\AuditLogs\Pages;

use App\Filament\Admin\Resources\AuditLogs\AuditLogResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAuditLog extends CreateRecord
{
    protected static string $resource = AuditLogResource::class;
}
