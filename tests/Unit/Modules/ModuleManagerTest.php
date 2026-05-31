<?php

declare(strict_types=1);

namespace Tests\Unit\Modules;

use App\Modules\ModuleManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleManagerTest extends TestCase
{
    use RefreshDatabase;

    private ModuleManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = new ModuleManager;
    }

    public function test_all_returns_collection(): void
    {
        $result = $this->manager->all();
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);
    }

    public function test_enabled_returns_only_enabled_modules(): void
    {
        $enabled = $this->manager->enabled();

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $enabled);

        foreach ($enabled as $module) {
            $this->assertTrue($module->isEnabled());
        }
    }

    public function test_disabled_returns_only_disabled_modules(): void
    {
        $disabled = $this->manager->disabled();

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $disabled);

        foreach ($disabled as $module) {
            $this->assertFalse($module->isEnabled());
        }
    }

    public function test_has_returns_false_for_unknown_module(): void
    {
        $this->assertFalse($this->manager->has('NonExistentModule'));
    }

    public function test_get_returns_null_for_unknown_module(): void
    {
        $this->assertNull($this->manager->get('NonExistentModule'));
    }

    public function test_enable_returns_false_for_unknown_module(): void
    {
        $this->assertFalse($this->manager->enable('NonExistentModule'));
    }

    public function test_disable_returns_false_for_unknown_module(): void
    {
        $this->assertFalse($this->manager->disable('NonExistentModule'));
    }

    public function test_health_check_returns_array(): void
    {
        $result = $this->manager->healthCheck();
        $this->assertIsArray($result);
    }

    public function test_get_all_modules_info_returns_array(): void
    {
        $result = $this->manager->getAllModulesInfo();
        $this->assertIsArray($result);
    }
}
