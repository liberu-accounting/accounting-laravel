<?php

declare(strict_types=1);

namespace App\Modules\Support;

use App\Modules\Contracts\ModuleInterface;
use App\Modules\ModuleManager;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class ExternalModuleLoader
{
    protected array $loadedPaths = [];

    public function __construct(protected ModuleManager $moduleManager) {}

    public function loadFromPath(string $path, string $namespace = 'Modules'): void
    {
        if (! File::exists($path) || ! File::isDirectory($path)) {
            Log::debug("External module path does not exist: {$path}");

            return;
        }

        if (in_array($path, $this->loadedPaths)) {
            return;
        }

        $this->loadedPaths[] = $path;

        foreach (File::directories($path) as $directory) {
            $this->loadModuleFromDirectory($directory, $namespace);
        }
    }

    protected function loadModuleFromDirectory(string $directory, string $baseNamespace): void
    {
        $moduleName = basename($directory);

        if (! File::exists($directory.'/module.json')) {
            return;
        }

        $candidates = [
            "{$baseNamespace}\\{$moduleName}\\{$moduleName}Module",
            "{$baseNamespace}\\{$moduleName}\\Module",
            "{$baseNamespace}\\{$moduleName}\\{$moduleName}",
        ];

        foreach ($candidates as $className) {
            if (! class_exists($className)) {
                continue;
            }

            try {
                $module = new $className;

                if ($module instanceof ModuleInterface) {
                    $this->moduleManager->register($module);
                    Log::info("Loaded external module: {$moduleName} from {$directory}");

                    return;
                }
            } catch (\Throwable $e) {
                Log::warning("Failed to instantiate module class {$className}: ".$e->getMessage());
            }
        }

        Log::debug("No valid module class found in {$directory}");
    }

    public function loadFromComposer(): void
    {
        $vendorPath = base_path('vendor');

        if (! File::exists($vendorPath)) {
            return;
        }

        foreach (File::directories($vendorPath) as $vendorDir) {
            foreach (File::directories($vendorDir) as $packageDir) {
                $modulesPath = $packageDir.'/modules';
                if (File::exists($modulesPath)) {
                    $packageName = basename(dirname($packageDir)).'/'.basename($packageDir);
                    Log::info("Loading modules from composer package: {$packageName}");
                    $this->loadFromPath($modulesPath, $this->getNamespaceFromComposer($packageDir));
                }
            }
        }
    }

    protected function getNamespaceFromComposer(string $packageDir): string
    {
        $composerJsonPath = $packageDir.'/composer.json';

        if (! File::exists($composerJsonPath)) {
            return 'Modules';
        }

        try {
            $composerData = json_decode(File::get($composerJsonPath), true);
            $namespaces = array_keys($composerData['autoload']['psr-4'] ?? []);

            if (! empty($namespaces)) {
                return rtrim($namespaces[0], '\\');
            }
        } catch (\Throwable $e) {
            Log::debug("Failed to parse composer.json in {$packageDir}: ".$e->getMessage());
        }

        return 'Modules';
    }

    public function registerCustomModule(string $modulePath, string $moduleClass): bool
    {
        if (! File::exists($modulePath)) {
            Log::warning("Custom module path does not exist: {$modulePath}");

            return false;
        }

        if (! class_exists($moduleClass)) {
            Log::warning("Custom module class does not exist: {$moduleClass}");

            return false;
        }

        try {
            $module = new $moduleClass;

            if (! $module instanceof ModuleInterface) {
                Log::warning("Custom module class does not implement ModuleInterface: {$moduleClass}");

                return false;
            }

            $this->moduleManager->register($module);
            Log::info("Registered custom module: {$module->getName()} from {$modulePath}");

            return true;
        } catch (\Throwable $e) {
            Log::error("Failed to register custom module {$moduleClass}: ".$e->getMessage());

            return false;
        }
    }

    public function getLoadedPaths(): array
    {
        return $this->loadedPaths;
    }
}
