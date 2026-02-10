<?php

declare(strict_types=1);

namespace Karnoweb\SmartCache\Traits;

use Karnoweb\SmartCache\Facades\SmartCache;

trait HasModelCache
{
    public static function bootHasModelCache(): void
    {
        $strategy = config('smart-cache.flush_strategy', 'all');

        if ($strategy === 'none') {
            return;
        }

        $flush = static function ($model): void {
            SmartCache::for($model::class)->flush();
        };

        static::created($flush);
        static::updated($flush);
        static::deleted($flush);
    }

    /**
     * دسترسی سریع به SmartCache برای این مدل.
     */
    public function cache(): \Karnoweb\SmartCache\SmartCache
    {
        return SmartCache::for(static::class);
    }

    public function putCache(string $key, mixed $value, mixed $ttl = null): void
    {
        $this->cache()->key($key)->put($value, $ttl);
    }

    public function getCache(string $key, mixed $default = null): mixed
    {
        return $this->cache()->key($key)->get($default);
    }

    public function forgetCache(string $key): void
    {
        $this->cache()->key($key)->forget();
    }

    public function flushCache(): void
    {
        $this->cache()->flush();
    }
}

