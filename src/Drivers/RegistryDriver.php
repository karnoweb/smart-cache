<?php

declare(strict_types=1);

namespace Karnoweb\SmartCache\Drivers;

use Carbon\Carbon;
use DateInterval;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\Repository;

class RegistryDriver implements CacheDriverInterface
{
    public function __construct(
        protected Repository $store,
    ) {
    }

    public function put(string $prefix, string $key, mixed $value, int|Carbon|DateInterval|null $ttl = null): void
    {
        $fullKey = $this->fullKey($prefix, $key);
        $this->store->put($fullKey, $value, $ttl);
        $this->addToRegistry($prefix, $fullKey);
    }

    public function get(string $prefix, string $key, mixed $default = null): mixed
    {
        return $this->store->get($this->fullKey($prefix, $key), $default);
    }

    public function has(string $prefix, string $key): bool
    {
        return $this->store->has($this->fullKey($prefix, $key));
    }

    public function forget(string $prefix, string $key): void
    {
        $fullKey = $this->fullKey($prefix, $key);
        $this->store->forget($fullKey);
        $this->removeFromRegistry($prefix, $fullKey);
    }

    public function flush(string $prefix): void
    {
        $registryKey = $this->registryKey($prefix);
        $keys = $this->store->get($registryKey, []);

        foreach ($keys as $key) {
            $this->store->forget($key);
        }

        $this->store->forget($registryKey);
    }

    public function lock(string $prefix, string $key, int $seconds, ?callable $callback = null): mixed
    {
        $store = $this->store->getStore();

        if ($store instanceof LockProvider) {
            $lockKey = "{$prefix}lock:{$key}";

            return $store
                ->lock($lockKey, $seconds)
                ->block($seconds, $callback);
        }

        // Fallback بدون lock — در سیستم‌های تک‌نخی مشکلی ندارد
        return $callback ? $callback() : null;
    }

    protected function fullKey(string $prefix, string $key): string
    {
        return $prefix . $key;
    }

    protected function registryKey(string $prefix): string
    {
        return $prefix . '__registry';
    }

    /**
     * ثبت کلید در رجیستری با استفاده از atomic operation
     * برای جلوگیری از race condition
     */
    protected function addToRegistry(string $prefix, string $fullKey): void
    {
        $registryKey = $this->registryKey($prefix);
        $store = $this->store->getStore();

        if ($store instanceof LockProvider) {
            $store
                ->lock("{$registryKey}:lock", 5)
                ->block(5, function () use ($registryKey, $fullKey) {
                    $keys = $this->store->get($registryKey, []);
                    if (! in_array($fullKey, $keys, true)) {
                        $keys[] = $fullKey;
                        $this->store->forever($registryKey, $keys);
                    }
                });
        } else {
            // Fallback بدون lock — در سیستم‌های تک‌نخی مشکلی ندارد
            $keys = $this->store->get($registryKey, []);
            if (! in_array($fullKey, $keys, true)) {
                $keys[] = $fullKey;
                $this->store->forever($registryKey, $keys);
            }
        }
    }

    protected function removeFromRegistry(string $prefix, string $fullKey): void
    {
        $registryKey = $this->registryKey($prefix);
        $store = $this->store->getStore();

        if ($store instanceof LockProvider) {
            $store
                ->lock("{$registryKey}:lock", 5)
                ->block(5, function () use ($registryKey, $fullKey) {
                    $keys = $this->store->get($registryKey, []);
                    $keys = array_values(array_filter($keys, fn ($k) => $k !== $fullKey));
                    $this->store->forever($registryKey, $keys);
                });
        } else {
            $keys = $this->store->get($registryKey, []);
            $keys = array_values(array_filter($keys, fn ($k) => $k !== $fullKey));
            $this->store->forever($registryKey, $keys);
        }
    }
}

