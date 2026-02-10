# 3. پیکربندی (Configuration)

تنظیمات در فایل `config/smart-cache.php` (یا از طریق متغیرهای محیطی) انجام می‌شود.

---

## گزینه‌های اصلی

| کلید | نوع | پیش‌فرض | توضیح |
|------|-----|---------|--------|
| `store` | `string\|null` | `null` | نام استور کش لاراول. اگر `null` باشد از استور پیش‌فرض (`config('cache.default')`) استفاده می‌شود. |
| `default_ttl` | `int\|null` | `3600` | زمان عمر پیش‌فرض آیتم‌های کش (ثانیه). `null` = بدون انقضا. |
| `global_prefix` | `string` | `sc` | پیشوندی که به همهٔ کلیدهای Smart Cache اضافه می‌شود. |
| `flush_strategy` | `string` | `all` | نحوهٔ flush خودکار با trait: `all`، `selective` یا `none`. |
| `stampede_protection` | `bool` | `true` | استفاده از lock در `remember()` برای جلوگیری از thundering herd. |
| `lock_timeout` | `int` | `5` | حداکثر زمان انتظار برای گرفتن قفل (ثانیه). |
| `lock_wait` | `int` | `5` | زمان انتظار در block قفل. |

---

## متغیرهای محیطی (Env)

می‌توانید در `.env` مقداردهی کنید:

```env
SMART_CACHE_STORE=redis
SMART_CACHE_TTL=3600
SMART_CACHE_PREFIX=sc
SMART_CACHE_FLUSH_STRATEGY=all
SMART_CACHE_STAMPEDE_PROTECTION=true
SMART_CACHE_LOCK_TIMEOUT=5
SMART_CACHE_LOCK_WAIT=5
```

---

## استور جداگانه برای Smart Cache

اگر بخواهید Smart Cache از استوری غیر از پیش‌فرض استفاده کند (مثلاً Redis در حالی که پیش‌فرض پروژه `file` است):

```env
SMART_CACHE_STORE=redis
```

مقادیر `store` باید با کلیدهای تعریف‌شده در `config/cache.php` (مثل `redis`, `file`, `database`) مطابقت داشته باشد.

---

## استراتژی Flush

- **`all`**: با هر رویداد created/updated/deleted روی مدل، تمام کش آن مدل flush می‌شود.
- **`selective`**: (برای استفادهٔ آینده) فقط کلیدهایی که به‌صورت «volatile» علامت‌گذاری شده‌اند با رویداد مدل flush می‌شوند.
- **`none`**: هیچ flush خودکاری انجام نمی‌شود؛ فقط flush دستی.

---

[← فهرست](0-index.md) · [قبلی: نصب](2-installation.md) · [بعدی: استفاده →](4-usage.md)
