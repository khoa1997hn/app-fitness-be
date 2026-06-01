# Migrations

## Tạo file
- BẮT BUỘC `sail exec --user sail laravel.test php artisan make:migration <name>`. CẤM tự tạo file thủ công.
- Tên: `create_<table>_table`, `add_<col>_to_<table>_table`, `change_<col>_in_<table>_table`.
- 1 migration = 1 thay đổi logic (tạo bảng / thêm cột / đổi cột). KHÔNG gộp nhiều bảng không liên quan.

## Field tối thiểu (rule 10)
- Mỗi field phải có chỗ dùng trong spec. Không có → KHÔNG thêm.
- Cấm phòng xa: `created_by`, `deleted_at` (soft delete), `status` (chỉ 1 trạng thái), `order`, `meta`/`extra`, `slug`, `uuid` — chỉ thêm khi spec yêu cầu.
- Giữ: `id` + `$table->timestamps()`.

## Kiểu dữ liệu
- Boolean → `boolean()`. Tiền → `decimal(p, s)` (KHÔNG `float`).
- Số nhỏ → `unsignedSmallInteger()`, ID lớn → `unsignedBigInteger()`.
- File field → `jsonb` (xem rule 12).

## Index & FK
- Index chỉ cho cột có query thực. Cấm "phòng xa".
- FK: `foreignId(...)->constrained()->onDelete(...)`. `onDelete` quyết định theo nghiệp vụ (`restrict` / `cascade` / `set null`).

## Translation table (rule 14)
- `<entity>_translations` BẮT BUỘC có: `locale` index, unique `<singular>_id + locale`, `cascadeOnDelete()`, KHÔNG timestamps.

## Migration đã chạy production
- KHÔNG sửa file gốc. Tạo migration mới để thay đổi.

Chi tiết: [`docs/rules/10-database-design.md`](../../docs/rules/10-database-design.md), [`07-seeders.md`](../../docs/rules/07-seeders.md), [`14-translatable.md`](../../docs/rules/14-translatable.md).
