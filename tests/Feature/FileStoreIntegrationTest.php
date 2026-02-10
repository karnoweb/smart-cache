<?php

declare(strict_types=1);

namespace Karnoweb\SmartCache\Tests\Feature;

use Karnoweb\SmartCache\Facades\SmartCache;
use Karnoweb\SmartCache\Tests\TestCase;

/**
 * Integration tests with File cache store (RegistryDriver).
 * Requires config override to use 'file' store.
 */
class FileStoreIntegrationTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);
        $app['config']->set('cache.default', 'file');
        $app['config']->set('cache.stores.file', [
            'driver' => 'file',
            'path' => storage_path('framework/cache/data'),
            'lock_path' => storage_path('framework/cache/data'),
        ]);
        $app['config']->set('smart-cache.store', 'file');
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->app['cache']->store('file')->flush();
    }

    public function test_put_get_flush_with_file_store(): void
    {
        SmartCache::for(\stdClass::class)->key('file-k')->put('file-value');
        $this->assertSame('file-value', SmartCache::for(\stdClass::class)->key('file-k')->get());
        SmartCache::for(\stdClass::class)->flush();
        $this->assertFalse(SmartCache::for(\stdClass::class)->key('file-k')->has());
    }
}
