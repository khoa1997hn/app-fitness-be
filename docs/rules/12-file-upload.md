# File upload

## Bắt buộc khi phân tích spec / thiết kế DB

Khi LLM đọc spec, mockup, hoặc thiết kế model/migration — **tự phân tích** field nào là dạng **upload file** (ảnh, video, tài liệu…). Nhận ra field upload → **KHÔNG chỉ** thêm cột `jsonb` + `FileCast`; phải **đồng thời** (hoặc trước khi code upload) hoàn tất 2 bước config:

1. Thêm **1 const** vào `app/Share/Enums/FileType.php` (mỗi loại file / mỗi field upload logic khác nhau = 1 const).
2. Thêm **1 entry** tương ứng vào `config/app_file.php` (`prefix_path`, `allow_mimetypes`, `allow_max_size`).

### Cách nhận diện field upload

- Spec/mockup mô tả: ảnh, cover, thumbnail, banner image, video, file đính kèm, upload…
- Nghiệp vụ lưu metadata file (path, tên gốc, dung lượng, loại) — dùng cột `jsonb` + `File` / `FileCast` (xem mục Model bên dưới).
- **Không** coi field chỉ là URL string thuần (`link_url` kiểu Banner) là upload — trừ khi spec yêu cầu upload file cho field đó.

### Đặt tên `FileType`

- Const **PascalCase**, value **snake_case** (giống `BannerImage` → `'banner_image'`).
- Gợi ý: `<Entity><Field>` hoặc `<Entity><Purpose>` — ví dụ `ProgramCover`, `LessonVideo`.
- Một const cho mỗi **cặp** (loại nội dung + rule validate khác nhau). Cùng rule (cùng mime/size/path) có thể tái dùng 1 const — ghi rõ trong spec/plan.

### Khi nào hỏi user (`AskUserQuestion`)

Mơ hồ → **BẮT BUỘC hỏi**, KHÔNG bịa `prefix_path` / mime / max size:

| Thông tin | Hỏi khi |
|-----------|---------|
| `prefix_path` | Chưa rõ thư mục lưu trên disk (ví dụ `program/cover` vs `programs/cover`). |
| `allow_mimetypes` | Chưa rõ ảnh vs video vs mix; hoặc spec không nêu định dạng. |
| `allow_max_size` | Chưa có giới hạn dung lượng (KB) — đưa 2–3 option (ví dụ ảnh 5MB, video 100MB). |

Có thể gom 1 lần hỏi (tối đa 4 câu theo `docs/guides/ask-protocol.md`). Sau khi user trả lời → ghi vào spec section **Quyết định**.

### Checklist trước khi coi field upload “xong”

- [ ] Đã thêm const `FileType::<Name>`.
- [ ] Đã thêm entry `config/app_file.php` với đủ 3 key.
- [ ] Migration: cột `jsonb` + Model Translation (nếu đa ngôn ngữ): cast `FileCast` + `@property File`.
- [ ] Form upload (phase sau): validate lấy từ `config('app_file.<snake_value>.…')`, upload qua `FileUploadService::upload(..., FileType::<Name>)`.

> Thiếu `FileType` hoặc thiếu entry `app_file.php` → coi là **vi phạm rule** (reviewer-rules / code-review-checklist).

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

- CẤM thêm cột `jsonb` + `FileCast` cho field upload mà **không** thêm `FileType` + `config/app_file.php` tương ứng.
- CẤM đoán `prefix_path` / `allow_mimetypes` / `allow_max_size` khi spec không rõ — phải `AskUserQuestion`.
- CẤM tự sinh path filename — phải qua `FileUploadService::upload()` (random 40 ký tự + extension gốc).
- CẤM dùng `Storage::disk('local')` cho file user upload — phải `public` để có URL truy cập.
- CẤM hardcode mimetypes/max_size — luôn lấy từ `config/app_file.php`.
- CẤM thêm `FileType` const mà không thêm config tương ứng — `FileUploadService` sẽ throw `InvalidArgumentException`.
- CẤM dùng `string` cho cột file trong migration (phải `jsonb` để lưu meta).
- CẤM bỏ PHPDoc `@property File $field` — mất type hint khi đọc.
