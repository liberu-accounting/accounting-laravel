<?php

namespace App\Listeners;

use App\Services\TeamManagementService;
use Illuminate\Auth\Events\Registered;

class CreatePersonalTeam
{
    public function __construct(protected \App\Services\TeamManagementService $teamManagementService)
    {
    }

    public function handle(Registered $event): void
    {
        $this->teamManagementService->assignUserToDefaultTeam($event->user);
    }
}