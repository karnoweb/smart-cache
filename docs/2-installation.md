# 2. نصب و راه‌اندازی (Installation)

## نصب با Composer

```bash
composer require karnoweb/smart-cache
```

پکیج به‌صورت خودکار در لاراول ثبت می‌شود (Service Provider و Facade از طریق `extra.laravel`).

---

## انتشار فایل پیکربندی

برای تغییر تنظیمات، فایل کانفیگ را در پروژه منتشر کنید:

```bash
php artisan vendor:publish --tag=smart-cache-config
```

این دستور فایل `config/smart-cache.php` را در پروژه ایجاد می‌کند. در صورت عدم انتشار، تنظیمات پیش‌فرض داخل پکیج استفاده می‌شوند.

---

## استفاده بدون Auto-Discovery (اختیاری)

اگر Auto-Discovery را غیرفعال کرده‌اید، در `config/app.php` به‌صورت دستی ثبت کنید:

**Providers:**

```php
'providers' => [
    // ...
    Karnoweb\SmartCache\SmartCacheServiceProvider::class,
],
```

**Aliases:**

```php
'aliases' => [
    // ...
    'SmartCache' => Karnoweb\SmartCache\Facades\SmartCache::class,
],
```

---

[← فهرست](0-index.md) · [قبلی: نمای کلی](1-overview.md) · [بعدی: پیکربندی →](3-configuration.md)
