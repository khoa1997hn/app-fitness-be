# File upload

## Stack

- Service: `app/Share/Services/File/FileUploadService.php` (readonly).
- Helper config: `app/Share/Services/File/FileConfigService.php`.
- Config: `config/app_file.php` (map theo `FileType` enum).
- Enum: `app/Share/Enums/FileType.php` (mỗi loại file 1 const).
- Value object: `app/Share/Attributes/File.php` (path / name / extension / size, có `url()`, implement `JsonSerializable`).
- Cast: `app/Share/Casts/FileCast.php` (Model field ↔ `File` object qua JSON).
- Storage: disk `public` (filesystem default).

## Khi thêm 1 loại file mới (ví dụ `LessonVideo`)

### Bước 1 — Thêm vào FileType enum
```php
// app/Share/Enums/FileType.php
final class FileType extends Enum
{
    const BannerImage = 'banner_image';
    const LessonVideo = 'lesson_video';   // mới
}
```

### Bước 2 — Thêm vào config/app_file.php
```php
return [
    FileType::BannerImage => [
        'prefix_path'     => 'banner/image',
        'allow_mimetypes' => ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'],
        'allow_max_size'  => 5120, // KB
    ],
    FileType::LessonVideo => [
        'prefix_path'     => 'lesson/video',
        'allow_mimetypes' => ['video/mp4', 'video/quicktime'],
        'allow_max_size'  => 102400, // KB (100MB)
    ],
];
```

### Bước 3 — Validate trong FormRequest
```php
$request->validate([
    'file' => [
        'required',
        'file',
        'mimetypes:' . implode(',', config('app_file.lesson_video.allow_mimetypes')),
        'max:' . config('app_file.lesson_video.allow_max_size'),
    ],
]);
```

### Bước 4 — Gọi service trong Controller
```php
public function store(Request $request, FileUploadService $upload)
{
    $file = $upload->upload($request->file('file'), FileType::LessonVideo);
    // $file là instance App\Share\Attributes\File
}
```

## Trong Model

### Bước 1 — Migration: dùng `jsonb`
```php
$table->jsonb('image');                 // not null
$table->jsonb('thumbnail')->nullable(); // nullable
```

### Bước 2 — Cast field qua `FileCast`
```php
protected function casts(): array
{
    return [
        'image' => FileCast::class,
    ];
}

protected $fillable = ['image', /* ... */];
```

### Bước 3 — PHPDoc `@property File $field`
```php
use App\Share\Attributes\File;

/**
 * @property File $image
 * @property File|null $thumbnail
 */
class Banner extends Model { ... }
```

→ IDE hint chuẩn, PHPStan pass.

## Response

Vì `File` implement `JsonSerializable` + `toArray()`, có thể trả thẳng:

```php
return ResponseAPI::success([
    'banner' => [
        'id'    => $banner->id,
        'image' => $banner->image,         // tự serialize thành { path, name, extension, size, url }
    ],
]);
```

Hoặc map từng field nếu chỉ cần URL:
```php
'image_url' => $banner->image?->url(),
```

## Cấm

- CẤM tự sinh path filename — phải qua `FileUploadService::upload()` (random 40 ký tự + extension gốc).
- CẤM dùng `Storage::disk('local')` cho file user upload — phải `public` để có URL truy cập.
- CẤM hardcode mimetypes/max_size — luôn lấy từ `config/app_file.php`.
- CẤM thêm `FileType` const mà không thêm config tương ứng — `FileUploadService` sẽ throw `InvalidArgumentException`.
- CẤM dùng `string` cho cột file trong migration (phải `jsonb` để lưu meta).
- CẤM bỏ PHPDoc `@property File $field` — mất type hint khi đọc.
