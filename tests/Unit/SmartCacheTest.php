<?php

declare(strict_types=1);

namespace Karnoweb\SmartCache\Tests\Unit;

use Karnoweb\SmartCache\Exceptions\InvalidCacheKeyException;
use Karnoweb\SmartCache\Facades\SmartCache;
use Karnoweb\SmartCache\Tests\TestCase;

class SmartCacheTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->app['cache']->flush();
    }

    public function test_put_and_get(): void
    {
        SmartCache::for(\stdClass::class)->key('k1')->put('value1');
        $this->assertSame('value1', SmartCache::for(\stdClass::class)->key('k1')->get());
    }

    public function test_get_returns_default_when_missing(): void
    {
        $this->assertNull(SmartCache::for(\stdClass::class)->key('missing')->get());
        $this->assertSame('default', SmartCache::for(\stdClass::class)->key('missing')->get('default'));
    }

    public function test_has(): void
    {
        SmartCache::for(\stdClass::class)->key('k1')->put('v');
        $this->assertTrue(SmartCache::for(\stdClass::class)->key('k1')->has());
        $this->assertFalse(SmartCache::for(\stdClass::class)->key('other')->has());
    }

    public function test_forget(): void
    {
        SmartCache::for(\stdClass::class)->key('k1')->put('v');
        SmartCache::for(\stdClass::class)->key('k1')->forget();
        $this->assertFalse(SmartCache::for(\stdClass::class)->key('k1')->has());
    }

    public function test_flush_clears_only_that_model_prefix(): void
    {
        SmartCache::for(\stdClass::class)->key('a')->put(1);
        SmartCache::for(\stdClass::class)->key('b')->put(2);
        SmartCache::for(\stdClass::class)->flush();
        $this->assertFalse(SmartCache::for(\stdClass::class)->key('a')->has());
        $this->assertFalse(SmartCache::for(\stdClass::class)->key('b')->has());
    }

    public function test_remember_stores_and_returns_value(): void
    {
        $called = 0;
        $result = SmartCache::for(\stdClass::class)
            ->key('rem')
            ->remember(function () use (&$called) {
                $called++;
                return 'computed';
            });
        $this->assertSame(1, $called);
        $this->assertSame('computed', $result);
        $this->assertSame('computed', SmartCache::for(\stdClass::class)->key('rem')->get());
        $result2 = SmartCache::for(\stdClass::class)->key('rem')->remember(fn () => 'other');
        $this->assertSame('computed', $result2);
        $this->assertSame(1, $called);
    }

    public function test_key_validation_empty_throws(): void
    {
        $this->expectException(InvalidCacheKeyException::class);
        $this->expectExceptionMessage('empty');
        SmartCache::for(\stdClass::class)->key('')->get();
    }

    public function test_key_validation_invalid_chars_throws(): void
    {
        $this->expectException(InvalidCacheKeyException::class);
        $this->expectExceptionMessage('invalid');
        SmartCache::for(\stdClass::class)->key('foo{bar')->get();
    }

    public function test_key_validation_too_long_throws(): void
    {
        $this->expectException(InvalidCacheKeyException::class);
        $this->expectExceptionMessage('250');
        SmartCache::for(\stdClass::class)->key(str_repeat('x', 251))->get();
    }

    public function test_operations_without_key_throw(): void
    {
        $this->expectException(InvalidCacheKeyException::class);
        $this->expectExceptionMessage('key');
        SmartCache::for(\stdClass::class)->get();
    }

    public function test_prefix_is_model_based(): void
    {
        $cache = SmartCache::for(\stdClass::class)->key('x');
        $this->assertStringContainsString('std-class', $cache->getPrefix());
        $this->assertStringStartsWith('sc:', $cache->getPrefix());
    }

    public function test_different_models_isolated(): void
    {
        SmartCache::for(\stdClass::class)->key('same')->put('std');
        SmartCache::for(\DateTimeInterface::class)->key('same')->put('date');
        $this->assertSame('std', SmartCache::for(\stdClass::class)->key('same')->get());
        $this->assertSame('date', SmartCache::for(\DateTimeInterface::class)->key('same')->get());
    }
}
