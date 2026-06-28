<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\App\Resources\AssetAcquisitions\AssetAcquisitionResource;
use App\Filament\App\Resources\Assets\AssetResource;
use App\Models\Module;
use App\Modules\FixedAssets\FixedAssetsModule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FixedAssetsModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_by_default_when_unmanaged(): void
    {
        $this->assertTrue(FixedAssetsModule::isActive());
        $this->assertTrue(AssetResource::canAccess());
        $this->assertTrue(AssetAcquisitionResource::canAccess());
    }

    public function test_disabling_module_removes_resource_access(): void
    {
        Module::create(['name' => FixedAssetsModule::KEY, 'enabled' => false]);

        $this->assertFalse(FixedAssetsModule::isActive());
        $this->assertFalse(AssetResource::canAccess());
        $this->assertFalse(AssetResource::shouldRegisterNavigation());
        $this->assertFalse(AssetAcquisitionResource::canAccess());
    }

    public function test_enabling_module_restores_resource_access(): void
    {
        Module::create(['name' => FixedAssetsModule::KEY, 'enabled' => true]);

        $this->assertTrue(FixedAssetsModule::isActive());
        $this->assertTrue(AssetResource::canAccess());
    }
}
