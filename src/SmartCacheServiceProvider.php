<?php

declare(strict_types=1);

namespace Karnoweb\SmartCache;

use Illuminate\Support\ServiceProvider;

class SmartCacheServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/smart-cache.php', 'smart-cache');

        $this->app->singleton('smartcache', function ($app) {
            $storeName = $app['config']->get('smart-cache.store');
            $cacheStore = $storeName
                ? $app['cache']->store($storeName)
                : $app['cache']->store();

            $manager = new SmartCacheManager(
                $cacheStore,
                $app['config']->get('smart-cache', []),
            );

            return $manager->build();
        });

        $this->app->alias('smartcache', SmartCache::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/smart-cache.php' => config_path('smart-cache.php'),
            ], 'smart-cache-config');
        }
    }
}

