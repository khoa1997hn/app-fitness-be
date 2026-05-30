# Spec: Upload / get file qua S3 presigned URL

## Bối cảnh

Toàn bộ file (ảnh + video) upload qua S3 private bucket: client lấy presigned PUT URL từ BE, upload trực tiếp lên S3; khi API trả file thì gen presigned GET. **Không dùng disk `public`**. Dự án chưa release — không cần fallback file cũ.

## Ghi chú gốc từ user (raw, không xóa)

- Phần response json HandlesPresignedFileUpload → dùng ResponseAPI
- Cần base JS admin: ajax lấy presigned URL + PUT lên S3 (blade tái sử dụng)
- Bỏ toàn bộ upload Storage disk public (logic + API)
- Không cover file cũ public disk *(Update 2026-05-29)*
- `file` trong response trả object `File` (JsonSerializable), không `toArray()` *(Update 2026-05-29)*
- Disk lấy tập trung qua `FileConfigService` (`default_disk`, `getDisk`, `getDiskForPath`) — không lặp `?? 's3'` *(Update 2026-05-29)*
- CẤM `ValidationException` trong Service — validate ở FormRequest; Service throw `InvalidFileInputException` *(Update 2026-05-29)*
- Rule tổng: ResponseAPI bắt buộc cho JSON API (Web + Admin) *(Update 2026-05-29)*
- LocalStack S3 trong docker-compose + `.env.example` preset *(Update 2026-05-29)*
- **Web không có API presigned-upload** — client Web chỉ đọc file qua presigned GET (`File::url()` trong response API khác); upload chỉ Admin *(Update 2026-05-29)*

## Phạm vi

### In-scope
- `FileUploadService`: `createPresignedUpload()` (Admin) + `getUrl()` (presigned GET S3 — Web + Admin).
- Tất cả `FileType` trong `config/app_file.php` → `disk: s3`.
- `POST /admin/files/presigned-upload` (Admin session).
- Admin JS base: `public/js/admin/s3-presigned-upload.js` + meta URL trong layout.
- `HandlesPresignedFileUpload` dùng `ResponseAPI::success()` (Admin).
- Web API: **không** endpoint upload; file field trong JSON response có `url` presigned GET.

### Out-of-scope
- Migrate data/file cũ.

## Nghiệp vụ

### Upload
1. Client gửi `type`, `filename`, `mimetype`, `size`.
2. **FormRequest** validate `type`, `filename` (có extension), `mimetype`, `size` theo config.
3. **Service** sinh path + presigned PUT; trả `file` là instance `File` (serialize qua `JsonSerializable`).
4. Client PUT lên S3.

### Disk config
- `config/app_file.php`: `default_disk` + per-type `disk`.
- `FileConfigService::getDisk()` / `getDiskForPath()` — mọi Storage call dùng chung.

### Get URL
- `FileUploadService::getUrl(path)` → `Storage::disk(getDiskForPath(path))->temporaryUrl(...)`.

## Input / Output

### POST presigned-upload (Admin)
Request:
```json
{
  "type": "program_cover",
  "filename": "cover.jpg",
  "mimetype": "image/jpeg",
  "size": 204800
}
```

Response (ResponseAPI):
```json
{
  "success": true,
  "message": "Success",
  "data": {
    "upload_url": "https://...",
    "method": "PUT",
    "headers": { "Content-Type": "image/jpeg" },
    "expires_in": 900,
    "file": { "path": "...", "name": "...", "extension": "jpg", "size": 204800, "url": "https://..." }
  }
}
```
(`file` là `File` object — JSON có thêm `url` presigned GET)

## Quyết định

- Presigned expiry: `AWS_PRESIGNED_URL_EXPIRES` (default 15 phút).
- PUT bind `ContentType` + `ContentLength`.
- **Không** disk public / direct upload / legacy URL fallback.
- Admin JS: `window.AdminS3Upload.upload(file, type)`.
- **2026-05-29** — Service exception: `InvalidFileInputException`, không `ValidationException`.
- **2026-05-29** — Local dev: LocalStack (`AWS_ENDPOINT=http://localstack:4566`, bucket `fitness-local`).
- **2026-05-29** — Web: không `POST /api/v1/files/presigned-upload`; không `FileController` Web. Upload presigned chỉ Admin.

## Liên quan

- `docs/rules/12-file-upload.md`, `docs/rules/02-code-quality.md`, `docs/rules/04-api-response.md`, `docs/rules/06-docker-sail.md`
