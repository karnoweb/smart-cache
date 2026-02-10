<?php

declare(strict_types=1);

namespace Karnoweb\SmartCache\Tests\Feature;

use Karnoweb\SmartCache\Facades\SmartCache;
use Karnoweb\SmartCache\Tests\TestCase;

class SmartCacheIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->app['cache']->flush();
    }

    public function test_facade_resolves_and_works_with_array_store(): void
    {
        SmartCache::for(\stdClass::class)->key('facade')->put('ok');
        $this->assertSame('ok', SmartCache::for(\stdClass::class)->key('facade')->get());
    }

    public function test_remember_integration(): void
    {
        $value = SmartCache::for(\stdClass::class)
            ->key('int-rem')
            ->remember(fn () => ['data' => 1]);
        $this->assertSame(['data' => 1], $value);
        $this->assertSame(['data' => 1], SmartCache::for(\stdClass::class)->key('int-rem')->get());
    }

    public function test_put_with_ttl_uses_config_default_when_null(): void
    {
        SmartCache::for(\stdClass::class)->key('ttl-test')->put('v', null);
        $this->assertSame('v', SmartCache::for(\stdClass::class)->key('ttl-test')->get());
    }
}
