<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use App\Modules\ModuleManager;
use App\Modules\ModuleServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Horizon\Horizon;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    #[\Override]
    public function register(): void
    {
        // Register the module manager as a singleton
        $this->app->singleton(ModuleManager::class, fn ($app): ModuleManager => new ModuleManager);

        // Register the module service provider
        $this->app->register(ModuleServiceProvider::class);
    }

    public function boot(): void
    {
        if (class_exists(Horizon::class)) {
            Horizon::auth(
                static fn ($request) => Gate::check('viewHorizon', [$request->user()]));
        }

        Gate::define('viewHorizon', static fn (User $user): bool => $user->hasRole('super_admin'));
        $this->configureModels();
        $this->configureUrl();
        $this->configurePassword();
    }

    private function configureModels(): void
    {
        Model::shouldBeStrict();
        Model::unguard();
        Model::automaticallyEagerLoadRelationships();
    }

    private function configureUrl(): void
    {
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }
    }

    private function configurePassword(): void
    {
        Password::defaults(
            static function () {
                return Password::min(12)        // NIST 800-63B: minimum 12 characters
                    ->mixedCase()      // At least one uppercase and one lowercase
                    ->numbers()        // At least one digit
                    ->symbols()        // At least one symbol (@$!%*#?&)
                    ->uncompromised(); // Check against breach database
            },
        );
    }
}
