<?php

declare(strict_types=1);

namespace Karnoweb\SmartCache\Tests;

use Karnoweb\SmartCache\SmartCacheServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            SmartCacheServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('cache.default', 'array');
        $app['config']->set('smart-cache.store', null);
        $app['config']->set('smart-cache.default_ttl', 3600);
        $app['config']->set('smart-cache.global_prefix', 'sc');
        $app['config']->set('smart-cache.stampede_protection', false);
    }
}
