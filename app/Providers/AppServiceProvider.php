<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Prevent N+1 queries in non-production environments
        Model::preventLazyLoading(! $this->app->isProduction());

        // Production passwords must not appear in known breaches.
        Password::defaults(fn (): Password => Password::min(8)->when(
            $this->app->isProduction(),
            fn (Password $rule): Password => $rule->uncompromised(),
        ));

        // Resolve factory names for modular domain models
        Factory::guessFactoryNamesUsing(function (string $modelName): string {
            $classBasename = class_basename($modelName);

            return 'Database\\Factories\\'.$classBasename.'Factory';
        });
    }
}
