<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Services\TeamManagementService;
use Illuminate\Auth\Events\Registered;

class CreatePersonalTeam
{
    public function __construct(protected TeamManagementService $teamManagementService) {}

    public function handle(Registered $event): void
    {
        $this->teamManagementService->assignUserToDefaultTeam($event->user);
    }
}
