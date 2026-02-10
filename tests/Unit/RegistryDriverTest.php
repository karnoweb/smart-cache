<?php

declare(strict_types=1);

namespace Karnoweb\SmartCache\Tests\Unit;

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Karnoweb\SmartCache\Drivers\RegistryDriver;
use PHPUnit\Framework\TestCase;

class RegistryDriverTest extends TestCase
{
    private Repository $store;
    private RegistryDriver $driver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->store = new Repository(new ArrayStore);
        $this->driver = new RegistryDriver($this->store);
    }

    public function test_put_get_has_forget(): void
    {
        $prefix = 'sc:user:';
        $key = 'features';
        $this->driver->put($prefix, $key, ['a', 'b']);
        $this->assertTrue($this->driver->has($prefix, $key));
        $this->assertSame(['a', 'b'], $this->driver->get($prefix, $key));
        $this->driver->forget($prefix, $key);
        $this->assertFalse($this->driver->has($prefix, $key));
        $this->assertNull($this->driver->get($prefix, $key));
    }

    public function test_flush_removes_all_keys_in_registry(): void
    {
        $prefix = 'sc:user:';
        $this->driver->put($prefix, 'k1', 1);
        $this->driver->put($prefix, 'k2', 2);
        $this->driver->flush($prefix);
        $this->assertFalse($this->driver->has($prefix, 'k1'));
        $this->assertFalse($this->driver->has($prefix, 'k2'));
    }

    public function test_flush_does_not_affect_other_prefix(): void
    {
        $this->driver->put('sc:user:', 'x', 1);
        $this->driver->put('sc:product:', 'x', 2);
        $this->driver->flush('sc:user:');
        $this->assertFalse($this->driver->has('sc:user:', 'x'));
        $this->assertTrue($this->driver->has('sc:product:', 'x'));
        $this->assertSame(2, $this->driver->get('sc:product:', 'x'));
    }

    public function test_lock_without_lock_provider_runs_callback(): void
    {
        $result = $this->driver->lock('p:', 'k', 1, fn () => 42);
        $this->assertSame(42, $result);
    }
}
