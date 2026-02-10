<?php

declare(strict_types=1);

namespace Karnoweb\SmartCache;

use Illuminate\Cache\TaggableStore;
use Illuminate\Contracts\Cache\Repository;
use Karnoweb\SmartCache\Drivers\CacheDriverInterface;
use Karnoweb\SmartCache\Drivers\RegistryDriver;
use Karnoweb\SmartCache\Drivers\TaggableDriver;

class SmartCacheManager
{
    protected CacheDriverInterface $driver;

    public function __construct(
        protected Repository $cacheStore,
        protected array $config,
    ) {
        $this->driver = $this->resolveDriver();
    }

    public function build(): SmartCache
    {
        return new SmartCache($this->driver, $this->config);
    }

    protected function resolveDriver(): CacheDriverInterface
    {
        $store = $this->cacheStore->getStore();

        if ($store instanceof TaggableStore) {
            return new TaggableDriver($this->cacheStore);
        }

        return new RegistryDriver($this->cacheStore);
    }
}

