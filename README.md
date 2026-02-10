# Smart Cache

کش وابسته به مدل برای لاراول با invalidation خودکار، پشتیبانی از تگ و محافظت در برابر cache stampede.

---

## نصب

```bash
composer require karnoweb/smart-cache
```

---

## استفادهٔ سریع

```php
use Karnoweb\SmartCache\Facades\SmartCache;
use App\Models\User;

// ذخیره و خواندن
SmartCache::for(User::class)->key('features')->put($features);
$features = SmartCache::for(User::class)->key('features')->get();

// remember با محافظت در برابر stampede
$data = SmartCache::for(User::class)
    ->key('dashboard')
    ->remember(fn () => expensiveQuery());
```

با trait روی مدل:

```php
use Karnoweb\SmartCache\Traits\HasModelCache;

class User extends Model
{
    use HasModelCache;
}
```

---

## مستندات

مستندات به صورت ساختاریافته و قابل گسترش در پوشهٔ **docs** قرار دارد. برای فهرست کامل و دسترسی به همهٔ بخش‌ها به فایل زیر مراجعه کنید:

**[📚 فهرست مستندات (docs/0-index.md)](docs/0-index.md)**

بخش‌های فعلی:

| # | موضوع |
|---|--------|
| 1 | [نمای کلی](docs/1-overview.md) — ویژگی‌ها و معماری |
| 2 | [نصب و راه‌اندازی](docs/2-installation.md) |
| 3 | [پیکربندی](docs/3-configuration.md) |
| 4 | [استفاده و API](docs/4-usage.md) |

برای افزودن مباحث جدید در آینده، فایل‌های شماره‌دار جدید (مثلاً `5-advanced.md`) را در `docs` اضافه کنید و لینک آن‌ها را در [docs/0-index.md](docs/0-index.md) ثبت کنید.

---

## نیازمندی‌ها

- PHP 8.2+
- Laravel 10.x / 11.x / 12.x

---

## لایسنس

MIT
