<?php

declare(strict_types = 1);

namespace DigitalCreative\Filepond;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Nova\Events\ServingNova;
use Laravel\Nova\Nova;

class FilepondServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/nova-filepond.php' => config_path('nova-filepond.php'),
        ], 'config');

        $this->app->booted(function (): void {
            $this->routes();
        });

        Nova::serving(function (ServingNova $event): void {
            Nova::script('filepond-main', __DIR__ . '/../dist/js/field.js');
        });
    }

    protected function routes(): void
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        Route::middleware([ 'nova' ])
            ->prefix('nova-vendor/nova-filepond')
            ->group(__DIR__ . '/../routes/api.php');
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/nova-filepond.php', 'nova-filepond');
    }
}
