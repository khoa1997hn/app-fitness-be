# File upload — S3 presigned

## Flow (Admin-only upload)
1. Admin client POST `/admin/files/presigned-upload` (session) với `{ type, filename, mimetype, size }`.
2. BE validate `type` ∈ `FileType` enum, `mimetype` ∈ `allow_mimetypes`, `size` ≤ `allow_max_size`.
3. BE trả `upload_url` (presigned PUT) + `headers` + `file` metadata qua `ResponseAPI::success()`.
4. Client PUT lên S3.
5. Client lưu `file` object vào entity (jsonb).

## Khi thêm 1 loại file mới
1. Thêm const PascalCase vào `app/Share/Enums/FileType.php` (value snake_case).
2. Thêm entry vào `config/app_file.php`: `prefix_path`, `allow_mimetypes`, `allow_max_size` (KB).
3. Validate trong FormRequest lấy từ `config('app_file.<value>.*')`.

## Model
- Migration: `jsonb` (KHÔNG `string`).
- Cast: `'image' => FileCast::class`.
- PHPDoc: `@property File $image` (`App\Share\Attributes\File`).
- Response: trả thẳng `$model->image` (tự serialize `{ path, name, extension, size, url }`).

## Disk
- **S3 only** cho mọi FileType. Presigned PUT (upload) + presigned GET (`File::url()`).
- Local dev: LocalStack (xem `docs/rules/06-docker-sail.md`).

## Khi mơ hồ
- Spec không nêu `prefix_path` / mimetypes / max_size → **AskUserQuestion**, KHÔNG bịa.

## Admin JS
- `await AdminS3Upload.upload(file, 'banner_image')` → `public/js/admin/s3-presigned-upload.js`.

Chi tiết: [`docs/rules/12-file-upload.md`](../../../../docs/rules/12-file-upload.md).
