# Plan: s3-presigned-upload

1. `composer require league/flysystem-aws-s3-v3`
2. Config: `app_file.php` disk + presigned expiry; `.env.example` AWS vars
3. `FileType::imageTypes()`, `FileConfigService` validate + resolve disk/path
4. Refactor `FileUploadService`: presigned upload + presigned get
5. Controllers Web + Admin
6. Update `12-file-upload.md`

## Update 2026-05-29 — S3-only + Admin JS

- HandlesPresignedFileUpload → ResponseAPI
- Bỏ upload() public disk + legacy getUrl fallback
- LessonVideo → disk s3
- public/js/admin/s3-presigned-upload.js + layout meta

## Update 2026-05-29 — Refine + LocalStack

- Return File object (not toArray)
- FileConfigService central disk (default_disk, getDiskForPath)
- Validation → FormRequest; Service → InvalidFileInputException
- Rules: no ValidationException in Service, ResponseAPI mandatory
- docker-compose localstack + init bucket script

## Update 2026-05-29 — Bỏ Web presigned-upload

- Xóa `app/Web/Http/Controllers/API/V1/FileController.php` + route `POST /api/v1/files/presigned-upload`.
- Giữ Admin upload + `FileUploadService::getUrl()` cho response Web (GET).
- Cập nhật `docs/rules/12-file-upload.md`; regenerate OpenAPI.
