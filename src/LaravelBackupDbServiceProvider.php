<?php

namespace Giobi\LaravelBackupDb;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class LaravelBackupDbServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Publish configuration
        $this->publishes([
            __DIR__ . '/../config/backup-manager.php' => config_path('backup-manager.php'),
        ], 'config');

        // Publish views
        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/laravel-backup-db'),
        ], 'views');

        // Load views
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'laravel-backup-db');

        // Register routes
        $this->registerRoutes();
    }

    public function register()
    {
        // Merge configuration
        $this->mergeConfigFrom(__DIR__ . '/../config/backup-manager.php', 'backup-manager');
    }

    protected function registerRoutes()
    {
        if (config('backup-manager.enable_routes', true)) {
            Route::group($this->routeConfiguration(), function () {
                $this->loadRoutesFrom(__DIR__ . '/routes.php');
            });
        }
    }

    protected function routeConfiguration()
    {
        return [
            'namespace' => 'Giobi\LaravelBackupDb\Http\Controllers',
            'prefix' => config('backup-manager.route_prefix', 'backups'),
            'middleware' => config('backup-manager.middleware', ['web']),
        ];
    }
}