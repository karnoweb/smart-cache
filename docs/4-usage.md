# 4. استفاده و API (Usage)

## استفادهٔ پایه با Facade

```php
use Karnoweb\SmartCache\Facades\SmartCache;
use App\Models\User;

// ذخیره
SmartCache::for(User::class)->key('features')->put($features);

// خواندن
$features = SmartCache::for(User::class)->key('features')->get();

// خواندن با مقدار پیش‌فرض
$features = SmartCache::for(User::class)->key('features')->get(null);

// بررسی وجود
if (SmartCache::for(User::class)->key('features')->has()) {
    // ...
}

// حذف یک کلید
SmartCache::for(User::class)->key('features')->forget();

// پاک کردن تمام کش‌های مدل User
SmartCache::for(User::class)->flush();
```

---

## remember — محاسبه و کش کردن

با `remember()` اگر مقدار در کش نباشد، callback اجرا می‌شود و نتیجه ذخیره می‌شود. در صورت فعال بودن `stampede_protection`، فقط یک درخواست callback را اجرا می‌کند و بقیه منتظر قفل می‌مانند.

```php
$features = SmartCache::for(User::class)
    ->key('features')
    ->remember(function () {
        return User::first()->getFeatureList();
    });

// با TTL سفارشی (ثانیه)
$features = SmartCache::for(User::class)
    ->key('features')
    ->remember(fn () => User::first()->getFeatureList(), 600);
```

---

## زنجیرهٔ Immutable

هر متد زنجیره‌ای (`for()`, `key()`, `volatile()`, `stable()`) یک **کپی** برمی‌گرداند؛ بنابراین استفادهٔ مجدد از همان instance باعث عوض شدن state قبلی نمی‌شود.

```php
$userCache = SmartCache::for(User::class);
$userCache->key('a')->put(1);
$userCache->key('b')->put(2);  // کلید هنوز 'b' است؛ هر بار key() یک clone با کلید جدید می‌سازد
```

برای استفادهٔ درست، هر بار زنجیره را از اول بسازید یا خروجی `key()` را نگه دارید.

---

## Trait HasModelCache

با استفاده از این trait روی مدل، با رویدادهای `created`، `updated` و `deleted` به‌طور خودکار تمام کش آن مدل flush می‌شود (مگر اینکه `flush_strategy` روی `none` باشد).

```php
use Illuminate\Database\Eloquent\Model;
use Karnoweb\SmartCache\Traits\HasModelCache;

class User extends Model
{
    use HasModelCache;
}
```

### متدهای کمکی روی مدل

```php
$user = User::find(1);

$user->putCache('preferences', $prefs);
$prefs = $user->getCache('preferences');
$user->forgetCache('preferences');
$user->flushCache();  // تمام کش‌های مدل User
```

`$user->cache()` یک instance از SmartCache برای همان مدل برمی‌گرداند:

```php
$user->cache()->key('features')->remember(fn () => $user->loadFeatures());
```

---

## ولیدیشن کلید

کلید نباید خالی باشد و نباید کاراکترهای `{ } ( ) / @ : \` داشته باشد؛ همچنین حداکثر طول ۲۵۰ کاراکتر است. در غیر این صورت `InvalidCacheKeyException` پرتاب می‌شود.

```php
use Karnoweb\SmartCache\Exceptions\InvalidCacheKeyException;

try {
    SmartCache::for(User::class)->key('')->get();
} catch (InvalidCacheKeyException $e) {
    // کلید نامعتبر
}
```

---

## volatile و stable

برای طراحی آیندهٔ «selective» flush می‌توان کلیدها را به‌صورت volatile یا stable علامت‌گذاری کرد. در نسخهٔ فعلی با `flush_strategy = all` هر دو مانند هم رفتار می‌کنند و با رویداد مدل همه flush می‌شوند.

```php
SmartCache::for(User::class)->key('list')->volatile()->put($list);
SmartCache::for(User::class)->key('config')->stable()->put($config);
```

---

[← فهرست](0-index.md) · [قبلی: پیکربندی](3-configuration.md)
