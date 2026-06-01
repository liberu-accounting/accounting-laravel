<?php

declare(strict_types=1);

namespace App\Modules;

use App\Models\Module;
use App\Modules\Contracts\ModuleInterface;
use App\Modules\Events\ModuleDisabled;
use App\Modules\Events\ModuleEnabled;
use App\Modules\Events\ModuleInstalled;
use App\Modules\Events\ModuleUninstalled;
use App\Modules\Traits\Configurable;
use App\Modules\Traits\HasModuleHooks;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use ReflectionClass;
use RuntimeException;

abstract class BaseModule implements ModuleInterface
{
    use Configurable, HasModuleHooks;

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
        try {
            $record = Module::findByName($this->getName());
            if ($record !== null) {
                return (bool) $record->enabled;
            }
        } catch (\Throwable $e) {
            Log::debug("Could not read module state from DB for {$this->getName()}: ".$e->getMessage());
        }

        return $this->config['enabled'] ?? false;
    }

    public function enable(): void
    {
        if ($this->isEnabled()) {
            Log::info("Module {$this->getName()} is already enabled, skipping.");

            return;
        }

        Log::info("Enabling module: {$this->getName()}");
        $this->executeHook('before_enable', $this);

        try {
            $this->onEnable();
        } catch (\Throwable $e) {
            $msg = "Failed to enable module {$this->getName()}: ".$e->getMessage();
            Log::error($msg, ['module' => $this->getName(), 'exception' => $e]);
            throw new RuntimeException($msg, 0, $e);
        }

        try {
            event(new ModuleEnabled($this->getName(), $this));
        } catch (\Throwable $e) {
            Log::debug("Failed to dispatch ModuleEnabled for {$this->getName()}: ".$e->getMessage());
        }

        $this->executeHook('after_enable', $this);
    }

    public function disable(): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $this->executeHook('before_disable', $this);

        try {
            $this->onDisable();
        } catch (\Throwable $e) {
            Log::warning("onDisable failed for {$this->getName()}: ".$e->getMessage());
        }

        try {
            event(new ModuleDisabled($this->getName(), $this));
        } catch (\Throwable $e) {
            Log::debug("Failed to dispatch ModuleDisabled for {$this->getName()}: ".$e->getMessage());
        }

        $this->executeHook('after_disable', $this);
    }

    public function install(): void
    {
        Log::info("Installing module: {$this->getName()}");
        $this->executeHook('before_install', $this);

        try {
            $this->runMigrations();
        } catch (\Throwable $e) {
            Log::error("Migration failed for module {$this->getName()}: ".$e->getMessage());
            throw $e;
        }

        try {
            $this->publishAssets();
        } catch (\Throwable $e) {
            Log::warning("Asset publishing failed for module {$this->getName()}: ".$e->getMessage());
        }

        $this->onInstall();
        $this->enable();

        try {
            event(new ModuleInstalled($this->getName(), $this));
        } catch (\Throwable $e) {
            Log::debug("Failed to dispatch ModuleInstalled for {$this->getName()}: ".$e->getMessage());
        }

        $this->executeHook('after_install', $this);
        Log::info("Module {$this->getName()} installed successfully");
    }

    public function uninstall(): void
    {
        $this->executeHook('before_uninstall', $this);

        $this->disable();
        $this->rollbackMigrations();
        $this->removeAssets();
        $this->onUninstall();

        try {
            event(new ModuleUninstalled($this->getName(), $this));
        } catch (\Throwable $e) {
            Log::debug("Failed to dispatch ModuleUninstalled for {$this->getName()}: ".$e->getMessage());
        }

        $this->executeHook('after_uninstall', $this);
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    protected function loadModuleInfo(): void
    {
        $moduleInfoPath = $this->getModulePath().'/module.json';

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
        return dirname((new ReflectionClass($this))->getFileName());
    }

    protected function runMigrations(): void
    {
        if (! preg_match('/^[a-zA-Z0-9_-]+$/', $this->name)) {
            Log::error("Invalid module name for migrations: {$this->name}");
            throw new \InvalidArgumentException("Invalid module name: {$this->name}");
        }

        $migrationsPath = $this->getModulePath().'/database/migrations';

        if (! File::exists($migrationsPath)) {
            return;
        }

        // Defense in depth: verify path stays within app/Modules
        $expectedPath = app_path('Modules/'.$this->name);
        $realModulePath = realpath($this->getModulePath());
        $realExpected = realpath($expectedPath);

        if ($realModulePath === false || $realExpected === false || ! str_starts_with($realModulePath, $realExpected)) {
            Log::error("Module path validation failed for: {$this->name}");
            throw new RuntimeException('Invalid module path');
        }

        Artisan::call('migrate', [
            '--path' => 'app/Modules/'.$this->name.'/database/migrations',
            '--force' => true,
        ]);
    }

    protected function rollbackMigrations(): void
    {
        if (! preg_match('/^[a-zA-Z0-9_-]+$/', $this->name)) {
            Log::error("Invalid module name for migration rollback: {$this->name}");
            throw new \InvalidArgumentException("Invalid module name: {$this->name}");
        }

        $migrationsPath = $this->getModulePath().'/database/migrations';

        if (! File::exists($migrationsPath)) {
            return;
        }

        $expectedPath = app_path('Modules/'.$this->name);
        $realModulePath = realpath($this->getModulePath());
        $realExpected = realpath($expectedPath);

        if ($realModulePath === false || $realExpected === false || ! str_starts_with($realModulePath, $realExpected)) {
            Log::error("Module path validation failed for: {$this->name}");
            throw new RuntimeException('Invalid module path');
        }

        try {
            Artisan::call('migrate:rollback', [
                '--path' => 'app/Modules/'.$this->name.'/database/migrations',
                '--force' => true,
            ]);
        } catch (\Throwable $e) {
            Log::warning("Failed to rollback migrations for {$this->getName()}: ".$e->getMessage());
        }
    }

    protected function publishAssets(): void
    {
        Artisan::call('vendor:publish', [
            '--tag' => strtolower($this->name).'-assets',
            '--force' => true,
        ]);
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
