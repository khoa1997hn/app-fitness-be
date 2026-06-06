# Plan: S3 media paths cho FakeDatabaseSeeder

## Phụ thuộc

- Migration: không
- Model: không đổi
- Config: không mới (`config/app_file.php` đã có prefix)

## Các pha

### Pha 1 — Spec + quyết định ASK
- Ghi spec.md với quyết định user đã chốt.

### Pha 2 — BannerFactory
- Const `BANNER_IMAGE_PATHS` (2–3 slot, TODO user điền).
- Helper `pickRandomFileFromPaths()`.
- `definition()`: vi + en cùng `image` random.

### Pha 3 — ProgramsSeeder
- Const `PROGRAM_COVER_PATHS`, `LESSON_THUMBNAIL_PATHS`, `LESSON_VIDEO_PATHS`.
- Thay path cố định bằng random từ pool.
- Giữ path thật hiện có làm fallback / mẫu trong comment.

## Verify thủ công

- [ ] `sail exec --user sail laravel.test php artisan db:seed --class=FakeDatabaseSeeder` chạy OK
- [ ] Sau khi user điền path thật: admin list program/banner hiện ảnh; lesson có thumbnail + video playable

## Update 2026-06-06

- Điền object key user cung cấp vào `BannerFactory` + `ProgramsSeeder`.
- Video pool: 1 key (đủ chạy; optional thêm sau).

### Verify
- [ ] `db:seed --class=FakeDatabaseSeeder` OK
- [ ] Admin/API hiển thị ảnh + video từ key trên bucket
