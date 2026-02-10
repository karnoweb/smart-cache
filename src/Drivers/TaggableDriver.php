<?php

declare(strict_types=1);

namespace Karnoweb\SmartCache\Drivers;

use Carbon\Carbon;
use DateInterval;
use Illuminate\Cache\TaggableStore;
use Illuminate\Contracts\Cache\Repository;

class TaggableDriver implements CacheDriverInterface
{
    public function __construct(
        protected Repository $store,
    ) {
    }

    protected function tagged(string $prefix): Repository
    {
        $store = $this->store->getStore();

        if (! $store instanceof TaggableStore) {
            throw new \RuntimeException('Underlying cache store does not support tags.');
        }

        /** @var \Illuminate\Cache\TaggedCache $tagged */
        $tagged = $store->tags([$prefix]);

        return $tagged;
    }

    public function put(string $prefix, string $key, mixed $value, int|Carbon|DateInterval|null $ttl = null): void
    {
        $this->tagged($prefix)->put($key, $value, $ttl);
    }

    public function get(string $prefix, string $key, mixed $default = null): mixed
    {
        return $this->tagged($prefix)->get($key, $default);
    }

    public function has(string $prefix, string $key): bool
    {
        return $this->tagged($prefix)->has($key);
    }

    public function forget(string $prefix, string $key): void
    {
        $this->tagged($prefix)->forget($key);
    }

    public function flush(string $prefix): void
    {
        $this->tagged($prefix)->flush();
    }

    public function lock(string $prefix, string $key, int $seconds, ?callable $callback = null): mixed
    {
        $lockKey = "{$prefix}lock:{$key}";

        return $this->store
            ->lock($lockKey, $seconds)
            ->block($seconds, $callback);
    }
}

