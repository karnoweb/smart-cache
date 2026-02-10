<?php

declare(strict_types=1);

namespace Karnoweb\SmartCache;

use Carbon\Carbon;
use DateInterval;
use Illuminate\Support\Str;
use Karnoweb\SmartCache\Drivers\CacheDriverInterface;
use Karnoweb\SmartCache\Exceptions\InvalidCacheKeyException;

class SmartCache
{
    protected string $prefix = '';
    protected string $key = '';
    protected bool $autoFlush = true;

    public function __construct(
        protected CacheDriverInterface $driver,
        protected array $config,
    ) {
    }

    /**
     * شروع زنجیره برای یک مدل مشخص.
     */
    public function for(string $modelClass): static
    {
        // هر بار یک clone جدید برمی‌گردد تا مشکل mutability نداشته باشیم
        $instance = clone $this;
        $instance->prefix = $this->buildPrefix($modelClass);
        $instance->key = '';
        $instance->autoFlush = true;

        return $instance;
    }

    /**
     * تعیین کلید کش.
     */
    public function key(string $key): static
    {
        $this->validateKey($key);
        $instance = clone $this;
        $instance->key = $key;

        return $instance;
    }

    /**
     * علامت‌گذاری این کلید برای selective auto-flush.
     */
    public function volatile(): static
    {
        $instance = clone $this;
        $instance->autoFlush = true;

        return $instance;
    }

    /**
     * علامت‌گذاری این کلید به عنوان stable (پاک نشود با auto-flush).
     */
    public function stable(): static
    {
        $instance = clone $this;
        $instance->autoFlush = false;

        return $instance;
    }

    /**
     * ذخیره‌ی مقدار.
     */
    public function put(mixed $value, int|Carbon|DateInterval|null $ttl = null): void
    {
        $this->ensureKeyIsSet();
        $ttl = $ttl ?? $this->config['default_ttl'] ?? null;
        $this->driver->put($this->prefix, $this->key, $value, $ttl);
    }

    /**
     * دریافت مقدار.
     */
    public function get(mixed $default = null): mixed
    {
        $this->ensureKeyIsSet();

        return $this->driver->get($this->prefix, $this->key, $default);
    }

    /**
     * بررسی وجود کلید.
     */
    public function has(): bool
    {
        $this->ensureKeyIsSet();

        return $this->driver->has($this->prefix, $this->key);
    }

    /**
     * دریافت یا محاسبه و ذخیره‌ی مقدار.
     * با محافظت در برابر cache stampede.
     */
    public function remember(callable $callback, int|Carbon|DateInterval|null $ttl = null): mixed
    {
        $this->ensureKeyIsSet();
        $ttl = $ttl ?? $this->config['default_ttl'] ?? null;

        // ابتدا بررسی وجود با has() به جای sentinel
        if ($this->driver->has($this->prefix, $this->key)) {
            return $this->driver->get($this->prefix, $this->key);
        }

        // stampede protection
        if ($this->config['stampede_protection'] ?? false) {
            $lockTimeout = $this->config['lock_timeout'] ?? 5;

            return $this->driver->lock(
                $this->prefix,
                $this->key,
                $lockTimeout,
                function () use ($callback, $ttl) {
                    // دوباره چک کن — شاید درخواست دیگری قبلاً پر کرده
                    if ($this->driver->has($this->prefix, $this->key)) {
                        return $this->driver->get($this->prefix, $this->key);
                    }

                    $value = $callback();
                    $this->driver->put($this->prefix, $this->key, $value, $ttl);

                    return $value;
                }
            );
        }

        $value = $callback();
        $this->driver->put($this->prefix, $this->key, $value, $ttl);

        return $value;
    }

    /**
     * حذف یک کلید.
     */
    public function forget(): void
    {
        $this->ensureKeyIsSet();
        $this->driver->forget($this->prefix, $this->key);
    }

    /**
     * پاک کردن تمام کش‌های مربوط به این مدل.
     */
    public function flush(): void
    {
        $this->driver->flush($this->prefix);
    }

    /**
     * Prefix فعلی را برمی‌گرداند (برای debug و تست).
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    protected function buildPrefix(string $modelClass): string
    {
        $globalPrefix = $this->config['global_prefix'] ?? 'sc';
        $modelPart = Str::kebab(class_basename($modelClass));

        return "{$globalPrefix}:{$modelPart}:";
    }

    protected function validateKey(string $key): void
    {
        if ($key === '') {
            throw new InvalidCacheKeyException('Cache key cannot be empty.');
        }

        if (preg_match('/[{}()\/@:\\\\]/', $key)) {
            throw new InvalidCacheKeyException(
                "Cache key contains invalid characters: {$key}"
            );
        }

        if (strlen($key) > 250) {
            throw new InvalidCacheKeyException(
                'Cache key exceeds maximum length of 250 characters.'
            );
        }
    }

    protected function ensureKeyIsSet(): void
    {
        if ($this->key === '') {
            throw new InvalidCacheKeyException(
                'No cache key set. Call ->key() before performing cache operations.'
            );
        }
    }
}

