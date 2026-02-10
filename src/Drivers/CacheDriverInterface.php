<?php

declare(strict_types=1);

namespace Karnoweb\SmartCache\Drivers;

use Carbon\Carbon;
use DateInterval;

interface CacheDriverInterface
{
    /**
     * Store a value under a specific model prefix and key.
     */
    public function put(string $prefix, string $key, mixed $value, int|Carbon|DateInterval|null $ttl = null): void;

    /**
     * Retrieve a value, or return default.
     */
    public function get(string $prefix, string $key, mixed $default = null): mixed;

    /**
     * Check if a key exists under the given prefix.
     */
    public function has(string $prefix, string $key): bool;

    /**
     * Remove a specific key.
     */
    public function forget(string $prefix, string $key): void;

    /**
     * Remove all keys under this prefix.
     */
    public function flush(string $prefix): void;

    /**
     * Acquire a lock for stampede protection.
     */
    public function lock(string $prefix, string $key, int $seconds, ?callable $callback = null): mixed;
}
