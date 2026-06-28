<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Module;
use App\Modules\Reconciliation\ReconciliationModule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReconciliationModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_by_default_when_unmanaged(): void
    {
        $this->assertTrue(ReconciliationModule::isActive());
    }

    public function test_disabling_module_gates_reconcile_workflow(): void
    {
        Module::create(['name' => ReconciliationModule::KEY, 'enabled' => false]);

        $this->assertFalse(ReconciliationModule::isActive());
    }

    public function test_enabling_module_restores_reconcile_workflow(): void
    {
        Module::create(['name' => ReconciliationModule::KEY, 'enabled' => true]);

        $this->assertTrue(ReconciliationModule::isActive());
    }
}
