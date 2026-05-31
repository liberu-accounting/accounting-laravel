<?php

declare(strict_types=1);

namespace App\Modules;

use App\Modules\Contracts\ModuleInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
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

    public function healthCheck(): array
    {
        $results = [];

        foreach ($this->enabled() as $module) {
            $results[$module->getName()] = method_exists($module, 'healthCheck') ? $module->healthCheck() : ['status' => 'ok'];
        }

        return $results;
    }

    protected function loadModules(): void
    {
        $cacheKey = config('modules.cache_key', 'app.modules');
        $cacheTtl = config('modules.cache_ttl', 3600);
        $useCache = config('modules.cache', true) && ! config('modules.development', false);

        if ($useCache) {
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                return;
            }
        }

        $modulesPath = config('modules.path', app_path('Modules'));

        if (! File::isDirectory($modulesPath)) {
            return;
        }

        foreach (File::directories($modulesPath) as $modulePath) {
            $this->loadModule(basename((string) $modulePath), $modulePath);
        }

        if ($useCache) {
            Cache::put($cacheKey, $this->getAllModulesInfo(), $cacheTtl);
        }
    }

    protected function loadModule(string $moduleName, string $modulePath): void
    {
        // Skip non-module directories (Events, Contracts, Traits, etc.)
        if (in_array($moduleName, ['Events', 'Contracts', 'Traits', 'Support'])) {
            return;
        }

        $moduleClass = sprintf('App\\Modules\\%s\\%sModule', $moduleName, $moduleName);

        if (class_exists($moduleClass)) {
            $module = new $moduleClass;
            if ($module instanceof ModuleInterface) {
                $this->register($module);
            }
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
