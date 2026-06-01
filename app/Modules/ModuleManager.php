<?php

declare(strict_types=1);

namespace App\Modules;

use App\Models\Module;
use App\Modules\Contracts\ModuleInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ModuleManager
{
    protected Collection $modules;

    public function __construct()
    {
        $this->modules = collect();
        $this->loadModules();
    }

    public function all(): Collection
    {
        return $this->modules;
    }

    public function enabled(): Collection
    {
        return $this->modules->filter(fn(ModuleInterface $m): bool => $m->isEnabled());
    }

    public function disabled(): Collection
    {
        return $this->modules->filter(fn(ModuleInterface $m): bool => ! $m->isEnabled());
    }

    public function get(string $name): ?ModuleInterface
    {
        return $this->modules->first(fn(ModuleInterface $m): bool => $m->getName() === $name);
    }

    public function has(string $name): bool
    {
        return $this->modules->contains(fn(ModuleInterface $m): bool => $m->getName() === $name);
    }

    public function enable(string $name): bool
    {
        $module = $this->get($name);

        if (! $module instanceof ModuleInterface) {
            return false;
        }

        if (! $this->checkDependencies($module)) {
            throw new RuntimeException("Module {$name} has unmet dependencies.");
        }

        $module->enable();
        $this->persistModuleState($module, enabled: true);
        $this->invalidateCache();

        return true;
    }

    public function disable(string $name): bool
    {
        $module = $this->get($name);

        if (! $module instanceof ModuleInterface) {
            return false;
        }

        if ($this->hasDependents($name)) {
            throw new RuntimeException("Cannot disable module {$name} as other modules depend on it.");
        }

        $module->disable();
        $this->persistModuleState($module, enabled: false);
        $this->invalidateCache();

        return true;
    }

    public function install(string $name): bool
    {
        $module = $this->get($name);

        if (! $module instanceof ModuleInterface) {
            return false;
        }

        if (! $this->checkDependencies($module)) {
            throw new RuntimeException("Module {$name} has unmet dependencies.");
        }

        $module->install();
        $this->persistModuleState($module, enabled: true);
        $this->invalidateCache();

        return true;
    }

    public function uninstall(string $name): bool
    {
        $module = $this->get($name);

        if (! $module instanceof ModuleInterface) {
            return false;
        }

        if ($this->hasDependents($name)) {
            throw new RuntimeException("Cannot uninstall module {$name} as other modules depend on it.");
        }

        $module->uninstall();
        $this->persistModuleState($module, enabled: false);
        $this->invalidateCache();

        return true;
    }

    public function register(ModuleInterface $module): void
    {
        $this->modules->put($module->getName(), $module);
    }

    public function getModuleInfo(string $name): array
    {
        $module = $this->get($name);

        if (! $module instanceof ModuleInterface) {
            return [];
        }

        return [
            'name'         => $module->getName(),
            'version'      => $module->getVersion(),
            'description'  => $module->getDescription(),
            'dependencies' => $module->getDependencies(),
            'enabled'      => $module->isEnabled(),
            'config'       => $module->getConfig(),
        ];
    }

    public function getAllModulesInfo(): array
    {
        return $this->modules->map(fn(ModuleInterface $m): array => $this->getModuleInfo($m->getName()))->values()->toArray();
    }

    public function clearCache(): void
    {
        $this->invalidateCache();
    }

    public function healthCheck(): array
    {
        $results = [];

        foreach ($this->modules as $module) {
            $results[$module->getName()] = $this->checkModuleHealth($module->getName());
        }

        return $results;
    }

    public function checkModuleHealth(string $name): array
    {
        $module = $this->get($name);

        if (! $module instanceof ModuleInterface) {
            return ['healthy' => false, 'errors' => ['Module not found'], 'warnings' => []];
        }

        $errors = [];
        $warnings = [];

        foreach ($module->getDependencies() as $dependency) {
            $dep = $this->get($dependency);
            if (! $dep instanceof ModuleInterface) {
                $errors[] = "Dependency {$dependency} not found";
            } elseif (! $dep->isEnabled()) {
                $warnings[] = "Dependency {$dependency} is disabled";
            }
        }

        if ($module->isEnabled() && ! $this->checkDependencies($module)) {
            $errors[] = 'Module is enabled but has unmet dependencies';
        }

        return [
            'healthy'  => empty($errors),
            'errors'   => $errors,
            'warnings' => $warnings,
        ];
    }

    protected function loadModules(): void
    {
        $cacheKey = config('modules.cache_key', 'app.modules');
        $cacheTtl = config('modules.cache_ttl', 3600);
        $useCache  = config('modules.cache', true) && ! config('modules.development', false);

        if ($useCache && Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);
            if (is_array($cached)) {
                $this->modules = collect($cached);

                return;
            }
        }

        // Load from app/Modules
        $modulesPath = config('modules.path', app_path('Modules'));

        if (File::isDirectory($modulesPath)) {
            foreach (File::directories($modulesPath) as $modulePath) {
                $this->loadModule(basename((string) $modulePath), $modulePath);
            }
        }

        // Load from app-modules (nWidart/modular-style packages)
        $modularPath = base_path(config('modular.modules_directory', 'app-modules'));

        if (File::isDirectory($modularPath)) {
            foreach (File::directories($modularPath) as $modulePath) {
                $this->loadModularModule(basename((string) $modulePath), $modulePath);
            }
        }

        if ($useCache) {
            Cache::put($cacheKey, $this->modules->all(), $cacheTtl);
        }
    }

    protected function loadModule(string $moduleName, string $modulePath): void
    {
        // Skip non-module directories
        if (in_array($moduleName, ['Events', 'Contracts', 'Traits', 'Support'])) {
            return;
        }

        $moduleClass = sprintf('App\\Modules\\%s\\%sModule', $moduleName, $moduleName);

        if (! class_exists($moduleClass)) {
            $mainFile = $modulePath."/{$moduleName}Module.php";
            if (File::exists($mainFile)) {
                try {
                    require_once $mainFile;
                } catch (\Throwable $e) {
                    Log::warning("Failed requiring main file for module {$moduleName}: ".$e->getMessage());
                }
            }
        }

        if (! class_exists($moduleClass)) {
            Log::warning("Module class {$moduleClass} not found for path {$modulePath}.");

            return;
        }

        try {
            $module = new $moduleClass;
        } catch (\Throwable $e) {
            Log::warning("Failed instantiating module {$moduleClass}: ".$e->getMessage());

            return;
        }

        if (! $module instanceof ModuleInterface) {
            return;
        }

        $this->register($module);
        $this->persistModuleMetadata($module);
    }

    protected function loadModularModule(string $moduleName, string $modulePath): void
    {
        $namespace = config('modular.modules_namespace', 'Modules');
        $moduleClass = "{$namespace}\\{$moduleName}\\{$moduleName}Module";

        if (! class_exists($moduleClass)) {
            return;
        }

        try {
            $wrapper = new class($moduleClass) implements ModuleInterface
            {
                private bool $enabled = false;

                private mixed $inner;

                public function __construct(private string $innerClass)
                {
                    if (class_exists($innerClass)) {
                        $this->inner = new $innerClass;
                    }

                    try {
                        $record = Module::where('name', $this->getName())->first();
                        $this->enabled = $record ? (bool) $record->enabled : false;
                    } catch (\Throwable) {}
                }

                public function getName(): string
                {
                    return ($this->inner && method_exists($this->inner, 'getName'))
                        ? $this->inner::getName()
                        : basename(str_replace('\\', '/', $this->innerClass));
                }

                public function getVersion(): string
                {
                    return ($this->inner && method_exists($this->inner, 'getVersion'))
                        ? $this->inner::getVersion()
                        : '1.0.0';
                }

                public function getDescription(): string
                {
                    return ($this->inner && method_exists($this->inner, 'getDescription'))
                        ? $this->inner::getDescription()
                        : '';
                }

                public function getDependencies(): array
                {
                    return [];
                }

                public function isEnabled(): bool
                {
                    return $this->enabled;
                }

                public function enable(): void
                {
                    $this->enabled = true;
                }

                public function disable(): void
                {
                    $this->enabled = false;
                }

                public function install(): void {}

                public function uninstall(): void {}

                public function getConfig(): array
                {
                    return config(strtolower($this->getName()), []);
                }
            };

            $this->register($wrapper);
            $this->persistModuleMetadata($wrapper);
        } catch (\Throwable $e) {
            Log::warning("Failed loading modular module '{$moduleName}': ".$e->getMessage());
        }
    }

    protected function persistModuleMetadata(ModuleInterface $module): void
    {
        try {
            Module::updateOrCreate(
                ['name' => $module->getName()],
                [
                    'version'      => $module->getVersion(),
                    'description'  => $module->getDescription(),
                    'dependencies' => $module->getDependencies(),
                    'config'       => $module->getConfig(),
                ]
            );
        } catch (\Throwable $e) {
            Log::warning("Failed to persist module '{$module->getName()}' metadata: ".$e->getMessage());
        }
    }

    protected function persistModuleState(ModuleInterface $module, bool $enabled): void
    {
        try {
            $record = Module::firstOrNew(['name' => $module->getName()]);
            $record->enabled     = $enabled;
            $record->version     = $module->getVersion();
            $record->description = $module->getDescription();
            $record->dependencies = $module->getDependencies();
            $record->config      = $module->getConfig();
            $record->save();
        } catch (\Throwable $e) {
            Log::warning("Failed to persist state for module '{$module->getName()}': ".$e->getMessage());
        }
    }

    protected function checkDependencies(ModuleInterface $module): bool
    {
        foreach ($module->getDependencies() as $dependency) {
            $dep = $this->get($dependency);
            if (! $dep?->isEnabled()) {
                return false;
            }
        }

        return true;
    }

    protected function hasDependents(string $moduleName): bool
    {
        return $this->enabled()->contains(
            fn(ModuleInterface $m): bool => in_array($moduleName, $m->getDependencies()),
        );
    }

    protected function invalidateCache(): void
    {
        Cache::forget(config('modules.cache_key', 'app.modules'));
    }
}
