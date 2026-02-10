<?php

declare(strict_types=1);

namespace Karnoweb\SmartCache\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Karnoweb\SmartCache\SmartCache for(string $class)
 * @method static \Karnoweb\SmartCache\SmartCache key(string $key)
 * @method static void put(mixed $value, mixed $ttl = null)
 * @method static mixed get(mixed $default = null)
 * @method static bool has()
 * @method static mixed remember(callable $callback, mixed $ttl = null)
 * @method static void forget()
 * @method static void flush()
 *
 * @see \Karnoweb\SmartCache\SmartCache
 */
class SmartCache extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'smartcache';
    }
}

