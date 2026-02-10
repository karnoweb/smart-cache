# 1. نمای کلی (Overview)

Smart Cache یک پکیج لاراول برای کش‌گیری **وابسته به مدل** است؛ کلیدهای کش بر اساس مدل (مثلاً `User`، `Product`) گروه‌بندی می‌شوند و می‌توان با یک فراخوانی تمام کش‌های مربوط به یک مدل را پاک کرد.

---

## چرا Smart Cache؟

- **جداسازی کش بر اساس مدل** — یکی از بزرگ‌ترین چالش‌های cache invalidation این است که بدانیم کدام کلیدها به کدام مدل وابسته‌اند. با Smart Cache هر مدل یک «فضای نام» کش دارد و `flush()` تمام کلیدهای همان مدل را پاک می‌کند.
- **API زنجیره‌ای و خوانا** — مثلاً: `SmartCache::for(User::class)->key('features')->get()`.
- **Driver-agnostic** — اگر استور کش از تگ پشتیبانی کند (مثل Redis)، از تگ استفاده می‌شود؛ در غیر این صورت از یک رجیستری (با lock برای جلوگیری از race condition) استفاده می‌شود.
- **Auto-invalidation** — با trait `HasModelCache` می‌توان روی رویدادهای مدل (created, updated, deleted) به‌طور خودکار کش آن مدل را flush کرد.
- **محافظت در برابر Cache Stampede** — در `remember()` با قفل می‌توان جلوی اجرای همزمان callback را گرفت.
- **ولیدیشن کلید** — کلید خالی، کاراکترهای نامعتبر و طول بیش از حد مجاز رد می‌شوند.

---

## معماری کلی

```
SmartCache (Facade / API)
       │
       ▼
SmartCacheManager  ──►  resolveDriver()
       │                      │
       │                      ├── TaggableStore?  → TaggableDriver (tags)
       │                      └── else            → RegistryDriver (registry + lock)
       │
       ▼
CacheDriverInterface  ←  put, get, has, forget, flush, lock
       │
       ├── TaggableDriver   (Redis, Memcached)
       └── RegistryDriver   (File, Database, Array)
```

- **SmartCache**: کلاس اصلی که با `for()` و `key()` زنجیره می‌سازد و عملیات را به درایور می‌سپارد.
- **SmartCacheManager**: استور کش لاراول را می‌گیرد و بر اساس پشتیبانی از تگ، درایور مناسب را انتخاب می‌کند.
- **Drivers**: منطق واقعی ذخیره/بازیابی؛ یا با تگ یا با رجیستری (و در صورت امکان با lock برای registry).

---

## پیش‌نیازها

- PHP 8.2+
- Laravel 10.x / 11.x / 12.x
- `illuminate/cache` و `illuminate/support`

---

[← فهرست](0-index.md) · [بعدی: نصب →](2-installation.md)
