# Đa ngôn ngữ (Translatable)

## Bối cảnh

Project bắt buộc đa ngôn ngữ **cho cả content**, không chỉ label. Tức là 1 banner / lesson / program có thể có ảnh, mô tả, link khác nhau giữa `vi` và `en`.

→ Khi thiết kế DB cho Web API, BẮT BUỘC cân nhắc: field nào cần đa ngôn ngữ, field nào dùng chung.

## Stack

- Package: **`astrotomic/laravel-translatable`**.
- Config: `config/translatable.php` — `locales: ['vi', 'en']`.
- Header API: `x-locale` (optional, default lấy từ `config('app.locale')`).

## Pattern 2-table

Mỗi entity cần dịch → 2 table + 2 model.

### Migration

```php
// Table chính — field dùng chung
Schema::create('banners', function (Blueprint $table) {
    $table->id();
    $table->string('description', 500)->nullable();   // field dùng chung
    $table->timestamps();
});

// Table translation — field theo locale
Schema::create('banner_translations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('banner_id')->constrained()->cascadeOnDelete();
    $table->string('locale')->index();
    $table->unique(['banner_id', 'locale']);

    // field đa ngôn ngữ
    $table->jsonb('image');
    $table->string('link_url')->nullable();
    $table->integer('order')->default(0);
    $table->boolean('is_active')->default(true);
});
```

Quy ước:
- Table translation: `<table_chính>_translations` (số nhiều + `_translations`).
- FK: `<singular>_id` (ví dụ `banner_id`).
- Index `locale`, unique `<singular>_id + locale` BẮT BUỘC.
- `cascadeOnDelete()` — xóa parent xóa translation.
- KHÔNG có `timestamps` trong translation table (theo convention Astrotomic + xem [`BannerTranslation`](../../app/Share/Models/BannerTranslation.php)).

### Model chính

```php
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;

/**
 * @property int $id
 * @property string|null $description
 * @property File $image            // field dịch — vẫn khai báo PHPDoc ở model chính
 * @property string|null $link_url
 * @property int $order
 * @property bool $is_active
 */
class Banner extends Model implements TranslatableContract
{
    use Translatable;

    protected $fillable = [
        'description',  // chỉ field dùng chung
    ];

    protected $translatedAttributes = [
        'image',
        'link_url',
        'order',
        'is_active',
    ];
}
```

### Model translation

```php
class BannerTranslation extends Model
{
    protected $fillable = [
        'image',
        'link_url',
        'order',
        'is_active',
    ];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'order'     => 'integer',
            'is_active' => 'boolean',
            'image'     => FileCast::class,
        ];
    }
}
```

→ Cast (file, enum, datetime…) đặt ở Translation model, KHÔNG ở model chính.

## Khi thiết kế field — cân nhắc

Tự hỏi: "Nếu PO bảo làm tiếng Nhật, field này có khác không?"

- **CÓ** (image, title, description, button label, link, sort order theo thị trường) → đặt ở translation table.
- **KHÔNG** (user-defined ID, mã code nội bộ, created_at, foreign keys) → đặt ở table chính.

Field nghi ngờ → hỏi user qua AskUserQuestion (`docs/rules/00-core.md`). KHÔNG bịa.

## Query / Access

### Truy cập field translated
```php
$banner = Banner::query()->find(1);

$banner->image;          // tự lấy theo current locale
$banner->link_url;
```

### Filter theo trường translated
Vì là eloquent-translatable, dùng helper của package:
```php
Banner::query()
    ->withTranslation()
    ->whereTranslation('is_active', true)
    ->orderByTranslation('order');
```

Xem [`BannerController::index`](../../app/Web/Http/Controllers/API/V1/BannerController.php) làm mẫu.

### Đổi locale current trong code
```php
app()->setLocale('en');
$banner->image; // bản en
```

## Response API

Map field translated y như field thường (Translatable trả về theo locale hiện tại):

```php
return ResponseAPI::success([
    'banner' => [
        'id'       => $banner->id,
        'image'    => $banner->image,
        'link_url' => $banner->link_url,
    ],
]);
```

Locale lấy từ header `x-locale` qua middleware (đã có sẵn).

## Cấm

- CẤM lưu nhiều giá trị locale vào 1 cột JSON (ví dụ `{"vi": "...", "en": "..."}`) — phải dùng translation table.
- CẤM bỏ index `locale` hoặc unique `<singular>_id + locale` — sẽ bị duplicate row + query chậm.
- CẤM cast field trong model chính cho field translated — cast ở Translation model.
- CẤM hardcode locale `'vi'` / `'en'` trong code — lấy từ `app()->getLocale()` hoặc `config('app.locale')`.
- CẤM bỏ qua đa ngôn ngữ khi thiết kế field "có khả năng" cần dịch — phải hỏi user.
