# 5. جزئیات فنی (Technical Details)

این سند جزئیات داخلی Smart Cache را توضیح می‌دهد: نحوهٔ flush، race conditionها، TTL، stampede protection، محدودیت‌ها و پشتیبانی از storeهای سفارشی.

---

## 1. انتخاب درایور و پشتیبانی از Storeهای سفارشی

Smart Cache **هر استوری را که لاراول پشتیبانی می‌کند** قبول می‌کند؛ انتخاب درایور داخلی بر اساس **قابلیت تگ** است:

| استور لاراول | درایور داخلی | نحوهٔ flush |
|---------------|--------------|-------------|
| **Redis**, **Memcached** | `TaggableDriver` | یک تگ به ازای هر مدل؛ `flush()` = `TaggedCache::flush()` روی آن تگ |
| **File**, **Database**, **Array**, **DynamoDB**, … | `RegistryDriver` | لیست کلیدها در یک آیتم کش به نام `{prefix}__registry`؛ `flush()` = خواندن لیست + `forget` هر کلید + `forget` رجیستری |

- **Store سفارشی**: هر نامی که در `config/cache.php` تعریف شده (مثلاً `redis`, `file`, `my_custom_store`) را می‌توان در `config('smart-cache.store')` قرار داد. پکیج همان استور را از `Cache::store($name)` می‌گیرد و بر اساس `getStore() instanceof TaggableStore` تصمیم می‌گیرد از تگ استفاده کند یا رجیستری.
- **چند استور**: می‌توان Smart Cache را روی یک استور (مثلاً Redis) و کش پیش‌فرض اپ را روی استور دیگری (مثلاً file) گذاشت تا مدل‌ها فقط روی استور مشخص کش شوند.

---

## 2. نحوهٔ دقیق Flush

### TaggableDriver (Redis / Memcached)

- هر مدل یک **تگ** دارد: مقدار همان `prefix` است (مثلاً `sc:user:`).
- همهٔ کلیدهای آن مدل تحت آن تگ ذخیره می‌شوند.
- **`flush($prefix)`**: فراخوانی `$store->tags([$prefix])->flush()` — در Redis/Memcached این عملیات native است و تمام کلیدهای آن تگ یکجا پاک می‌شوند؛ تعداد کلیدها روی کارایی flush تأثیر زیادی نمی‌گذارد.

### RegistryDriver (File / Database / Array)

- هر مدل یک **رجیستری** دارد: یک کلید کش به نام `{prefix}__registry` که مقدارش **آرایهٔ تمام کلیدهای کامل** آن مدل است (مثلاً `['sc:user:features', 'sc:user:dashboard']`).
- **`put`**: مقدار در کلید `{prefix}{key}` ذخیره می‌شود؛ سپس کلید کامل به رجیستری **اضافه** می‌شود (با lock اگر استور `LockProvider` داشته باشد).
- **`flush($prefix)`**:
  1. خواندن `get($registryKey, [])` — لیست کلیدهای کامل.
  2. برای هر کلید: `forget($key)`.
  3. در پایان: `forget($registryKey)`.

**نکتهٔ مهم**: در RegistryDriver، رجیستری **یک آرایهٔ بزرگ** است. اگر یک مدل هزاران کلید داشته باشد، این آرایه بزرگ می‌شود و هم ذخیره/بارگذاری آن و هم حلقهٔ `forget` در flush می‌تواند سنگین شود. برای مدل‌هایی با تعداد کلید خیلی زیاد، استفاده از استور تگ‌دار (Redis/Memcached) توصیه می‌شود.

---

## 3. Race Condition در Registry

### مشکل

در RegistryDriver، `addToRegistry` و `removeFromRegistry` به صورت «خواندن لیست → تغییر → نوشتن» عمل می‌کنند. بدون قفل، دو درخواست همزمان می‌توانند هر دو لیست قدیمی را بخوانند و یکی از به‌روزرسانی‌ها گم شود.

### راه‌حل پیاده‌سازی‌شده

- اگر استور **`LockProvider`** را پیاده کند (مثلاً Redis، Memcached، یا File در نسخه‌های جدید لاراول که lock دارند)، قبل از خواندن/نوشتن رجیستری یک **قفل** روی کلید `{registryKey}:lock` گرفته می‌شود و عملیات داخل `block(5, callback)` انجام می‌شود. فقط یک فرآیند در هر لحظه رجیستری را تغییر می‌دهد؛ بنابراین race در register/unregister **حل شده** است.
- اگر استور **LockProvider نداشته باشد** (مثلاً بعضی پیکربندی‌های Array یا استورهای سفارشی بدون lock)، کد به حالت **fallback بدون قفل** می‌رود: همان read-modify-write. در این حالت در محیط **چندنخی/چند worker همزمان** احتمال از دست رفتن به‌روزرسانی وجود دارد؛ در محیط **تک‌نخی** مشکلی پیش نمی‌آید.

**خلاصه**: با Redis (یا هر استوری که `LockProvider` دارد)، race در registry **حل شده** است. با File/Array بدون lock، فقط در محیط تک‌نخی امن است.

---

## 4. Stampede Protection و Atomicity در `remember`

### مشکل

اگر کش خالی باشد و چندین درخواست همزمان `remember` را صدا بزنند، همه callback را اجرا می‌کنند (cache stampede / thundering herd).

### راه‌حل پیاده‌سازی‌شده

- وقتی `stampede_protection` در config روشن باشد، قبل از اجرای callback از **قفل کش** استفاده می‌شود:
  - `SmartCache::remember` ابتدا با `has()` چک می‌کند که کلید وجود داشته باشد؛ اگر بود با `get()` برمی‌گرداند.
  - اگر نبود، `driver->lock(prefix, key, lock_timeout, callback)` صدا زده می‌شود.
- در **TaggableDriver**: این قفل با `$this->store->lock($lockKey, $seconds)->block($seconds, $callback)` پیاده شده — یعنی همان **`Cache::lock()`** لاراول با `block()`.
- در **RegistryDriver**: اگر استور `LockProvider` داشته باشد، همان الگو با `$store->lock(...)->block(...)` استفاده می‌شود؛ وگرنه قفل وجود ندارد و callback مستقیماً اجرا می‌شود (فقط در محیط تک‌نخی امن است).

داخل callback دوباره `has()` چک می‌شود: اگر درخواست دیگری در فاصلهٔ گرفتن قفل تا اجرای callback مقدار را پر کرده باشد، از کش خوانده می‌شود و callback دوباره اجرا نمی‌شود.

**خلاصه**: با استوری که lock پشتیبانی کند، stampede با **Cache::lock + block** حل شده است؛ بدون lock فقط در تک‌نخی امن است.

---

## 5. Default TTL

- مقدار **`config('smart-cache.default_ttl')`** (ثانیه؛ پیش‌فرض ۳۶۰۰) وقتی استفاده می‌شود که به `put()` یا `remember()` مقدار TTL داده نشود (یا `null` داده شود).
- اگر در config مقدار `default_ttl` را `null` بگذارید، آیتم‌ها بدون انقضای زمانی ذخیره می‌شوند (در عمل بسته به استور ممکن است «برای همیشه» یا با سیاست خود استور منقضی شوند).
- ارسال صریح TTL به `put($value, $ttl)` یا `remember($callback, $ttl)` همیشه اولویت دارد و default_ttl را نادیده می‌گیرد.

---

## 6. محدودیت‌های فعلی و کارهای آینده

| موضوع | وضعیت فعلی | نکته / کار آینده |
|--------|------------|-------------------|
| **Flush تهاجمی** | با `flush_strategy = all`، هر رویداد created/updated/deleted روی **هر رکورد** از آن مدل، **تمام** کش آن مدل را flush می‌کند. | در مدل‌های با کلید زیاد یا ترافیک بالا می‌تواند کش را بی‌فایده کند. هدف آینده: **granular invalidation** (مثلاً فقط کلیدهای وابسته به یک رکورد خاص). |
| **رجیستری بزرگ** | در RegistryDriver تمام کلیدهای یک مدل در **یک آرایه** در یک آیتم کش نگه داشته می‌شود. | با هزاران کلید، اندازهٔ این آرایه و زمان flush بالا می‌رود. برای مدل‌های با کلید بسیار زیاد استفاده از استور تگ‌دار (Redis) توصیه می‌شود. |
| **Selective flush** | مقدار `flush_strategy = selective` در config وجود دارد ولی در trait فعلاً مانند `all` رفتار می‌کند (همهٔ کلیدها با رویداد مدل flush می‌شوند). | بعداً می‌توان فقط کلیدهای علامت‌گذاری‌شده به‌عنوان volatile را با رویداد مدل flush کرد. |
| **Per-record invalidation** | وجود ندارد. | بعداً می‌توان کلیدهایی که به یک رکورد خاص (مثلاً `user:123`) وابسته‌اند را جداگانه invalidate کرد تا فقط با تغییر همان رکورد پاک شوند. |

---

## 7. خلاصهٔ امنیت در محیط concurrent

- **TaggableDriver**: flush اتمیک (native tag flush)، register/unregister لازم نیست؛ برای `remember` از Cache::lock استفاده می‌شود → مناسب production با Redis/Memcached.
- **RegistryDriver با LockProvider**: register/unregister با lock روی رجیستری → race حل شده؛ remember با lock → stampede حل شده.
- **RegistryDriver بدون LockProvider**: بدون lock؛ فقط در محیط تک‌نخی یا بدون همزمانی زیاد قابل اطمینان است.

---

[← فهرست](0-index.md) · [قبلی: استفاده](4-usage.md)
