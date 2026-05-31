<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use App\Modules\ModuleManager;
use App\Modules\ModuleServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    #[\Override]
    public function register(): void
    {
        // Register the module manager as a singleton
        $this->app->singleton(ModuleManager::class, fn($app): \App\Modules\ModuleManager => new ModuleManager());

        // Register the module service provider
        $this->app->register(ModuleServiceProvider::class);
    }

    public function boot(): void
    {
        if (class_exists(\Laravel\Horizon\Horizon::class)) {
            \Laravel\Horizon\Horizon::auth(fn($request) => Gate::check('viewHorizon', [$request->user()]));
        }

        Gate::define('viewHorizon', fn(User $user): bool => $user->hasRole('super_admin'));
    }
}
