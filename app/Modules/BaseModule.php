<?php

declare(strict_types=1);

namespace App\Modules;

use App\Modules\Contracts\ModuleInterface;
use App\Modules\Events\ModuleDisabled;
use App\Modules\Events\ModuleEnabled;
use App\Modules\Events\ModuleInstalled;
use App\Modules\Events\ModuleUninstalled;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use ReflectionClass;

abstract class BaseModule implements ModuleInterface
{
    protected string $name;
    protected string $version;
    protected string $description;
    protected array $dependencies = [];
    protected array $config = [];

    public function __construct()
    {
        $this->loadModuleInfo();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    public function isEnabled(): bool
    {
        return (bool) Cache::get("module.{$this->name}.enabled", false);
    }

    public function enable(): void
    {
        $this->onEnable();
        Cache::put("module.{$this->name}.enabled", true);
        Event::dispatch(new ModuleEnabled($this));
    }

    public function disable(): void
    {
        $this->onDisable();
        Cache::put("module.{$this->name}.enabled", false);
        Event::dispatch(new ModuleDisabled($this));
    }

    public function install(): void
    {
        $this->runMigrations();
        $this->publishAssets();
        $this->onInstall();
        $this->enable();
        Event::dispatch(new ModuleInstalled($this));
    }

    public function uninstall(): void
    {
        $this->disable();
        $this->onUninstall();
        $this->rollbackMigrations();
        $this->removeAssets();
        Event::dispatch(new ModuleUninstalled($this));
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function healthCheck(): array
    {
        return ['status' => 'ok', 'module' => $this->name];
    }

    protected function loadModuleInfo(): void
    {
        $modulePath = $this->getModulePath();
        $moduleInfoPath = $modulePath.'/module.json';

        if (File::exists($moduleInfoPath)) {
            $moduleInfo = json_decode(File::get($moduleInfoPath), true) ?? [];

            $this->name = $moduleInfo['name'] ?? class_basename($this);
            $this->version = $moduleInfo['version'] ?? '1.0.0';
            $this->description = $moduleInfo['description'] ?? '';
            $this->dependencies = $moduleInfo['dependencies'] ?? [];
            $this->config = $moduleInfo['config'] ?? [];
        }
    }

    protected function getModulePath(): string
    {
        $reflection = new ReflectionClass($this);

        return dirname($reflection->getFileName());
    }

    protected function runMigrations(): void
    {
        // Validate module name to prevent path traversal
        if (! preg_match('/^[a-zA-Z0-9_-]+$/', $this->name)) {
            return;
        }

        $migrationsPath = $this->getModulePath().'/database/migrations';

        if (File::isDirectory($migrationsPath)) {
            Artisan::call('migrate', [
                '--path' => 'app/Modules/'.$this->name.'/database/migrations',
                '--force' => true,
            ]);
        }
    }

    protected function rollbackMigrations(): void {}

    protected function publishAssets(): void
    {
        $tag = strtolower($this->name).'-assets';
        Artisan::call('vendor:publish', ['--tag' => $tag, '--force' => true]);
    }

    protected function removeAssets(): void
    {
        $assetsPath = public_path("modules/{$this->name}");
        if (File::isDirectory($assetsPath)) {
            File::deleteDirectory($assetsPath);
        }
    }

    protected function onEnable(): void {}

    protected function onDisable(): void {}

    protected function onInstall(): void {}

    protected function onUninstall(): void {}
}
