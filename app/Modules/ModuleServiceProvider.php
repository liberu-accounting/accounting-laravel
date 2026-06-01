<?php

declare(strict_types=1);

namespace App\Modules;

use App\Models\Module;
use App\Modules\Support\ExternalModuleLoader;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class ModuleServiceProvider extends ServiceProvider
{
    #[\Override]
    public function register(): void
    {
        $this->registerModules();
        $this->registerExternalModules();
    }

    public function boot(): void
    {
        $this->bootModules();
    }

    protected function registerModules(): void
    {
        $modulesPath = app_path('Modules');

        if (! File::exists($modulesPath)) {
            return;
        }

        foreach (File::directories($modulesPath) as $modulePath) {
            $this->registerModule(basename((string) $modulePath), $modulePath);
        }
    }

    protected function registerModule(string $moduleName, string $modulePath): void
    {
        // Register service provider unconditionally so the module can boot itself
        $providerPath = $modulePath.'/Providers/'.$moduleName.'ServiceProvider.php';
        if (File::exists($providerPath)) {
            $providerClass = "App\\Modules\\{$moduleName}\\Providers\\{$moduleName}ServiceProvider";
            if (class_exists($providerClass)) {
                $this->app->register($providerClass);
            }
        }

        // Always merge configuration
        $configPath = $modulePath.'/config';
        if (File::exists($configPath)) {
            foreach (File::files($configPath) as $configFile) {
                $configName = Str::snake($moduleName).'.'.$configFile->getFilenameWithoutExtension();
                $this->mergeConfigFrom($configFile->getPathname(), $configName);
            }
        }

        // Only register routes/views/translations for enabled modules
        if ($this->isModuleEnabled($moduleName)) {
            $this->registerModuleRoutes($moduleName, $modulePath);

            $viewsPath = $modulePath.'/resources/views';
            if (File::exists($viewsPath)) {
                $this->loadViewsFrom($viewsPath, Str::snake($moduleName));
            }

            $langPath = $modulePath.'/resources/lang';
            if (File::exists($langPath)) {
                $this->loadTranslationsFrom($langPath, Str::snake($moduleName));
            }
        }

        // Migrations always registered so artisan commands work
        $migrationsPath = $modulePath.'/database/migrations';
        if (File::exists($migrationsPath)) {
            $this->loadMigrationsFrom($migrationsPath);
        }
    }

    protected function registerModuleRoutes(string $moduleName, string $modulePath): void
    {
        $routesPath = $modulePath.'/routes';

        if (! File::exists($routesPath)) {
            return;
        }

        foreach (['web.php', 'api.php', 'admin.php'] as $routeFile) {
            $path = $routesPath.'/'.$routeFile;
            if (File::exists($path)) {
                $this->loadRoutesFrom($path);
            }
        }
    }

    protected function bootModules(): void
    {
        $modulesPath = app_path('Modules');

        if (! File::exists($modulesPath)) {
            return;
        }

        foreach (File::directories($modulesPath) as $modulePath) {
            $this->bootModule(basename((string) $modulePath), $modulePath);
        }
    }

    protected function bootModule(string $moduleName, string $modulePath): void
    {
        $assetsPath = $modulePath.'/resources/assets';
        if (File::exists($assetsPath)) {
            $this->publishes([
                $assetsPath => public_path("modules/{$moduleName}"),
            ], Str::snake($moduleName).'-assets');
        }

        $configPath = $modulePath.'/config';
        if (File::exists($configPath)) {
            foreach (File::files($configPath) as $configFile) {
                $this->publishes([
                    $configFile->getPathname() => config_path(Str::snake($moduleName).'.'.$configFile->getFilename()),
                ], Str::snake($moduleName).'-config');
            }
        }
    }

    protected function isModuleEnabled(string $moduleName): bool
    {
        try {
            if (class_exists(Module::class)) {
                $record = Module::where('name', $moduleName)->first();
                if ($record !== null) {
                    return (bool) $record->enabled;
                }
            }
        } catch (\Throwable) {
            // DB not yet set up — default to enabled
        }

        return true;
    }

    protected function registerExternalModules(): void
    {
        if (! config('modules.load_composer_modules', false)) {
            return;
        }

        try {
            $moduleManager = app(ModuleManager::class);
            $loader = new ExternalModuleLoader($moduleManager);

            $loader->loadFromComposer();

            foreach (config('modules.external_paths', []) as $path) {
                if (is_string($path) && $path !== '') {
                    $loader->loadFromPath($path);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to load external modules: '.$e->getMessage());
        }
    }
}
