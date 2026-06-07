# Bug report: InvalidArgumentException khi lưu combo cover

## Mô tả

**Triệu chứng:** `InvalidArgumentException` tại `app/Share/Casts/FileCast.php:38` — `The cover attribute must be a File instance or an array.`

**Reproduce:** Admin tạo/sửa combo — nhập tên locale `en` nhưng không upload cover (hoặc cover gửi lên là mảng không có `path` hợp lệ) → submit form → crash khi lưu translation.

**Mong đợi:** Cover optional với locale không bắt buộc → lưu `null` trong DB.

## Phân loại

- Mức độ: trung bình (chặn CRUD combo)
- Phạm vi: Admin combo create/update, có thể ảnh hưởng mọi field File nullable

## Nguyên nhân gốc

`FileCast::set()` gọi `File::fromArray($value)` khi nhận mảng. Nếu mảng thiếu key `path`, `fromArray()` trả `null` — nhưng cast vẫn throw vì `null` không phải `File`.

`ComboController::fillTranslations()` truyền thẳng `$data['cover']` kể cả mảng rỗng/không có path.

## Cách fix

1. `FileCast`: nếu `fromArray()` trả `null` → persist `null` (nullable file field).
2. `File::fromArray()`: coi `path` rỗng là `null`.
3. `ComboController`: chuẩn hóa `cover`/`icon` — chỉ gán khi có `path` non-empty.

## Files đã sửa

- `app/Share/Casts/FileCast.php` — `fromArray()` trả `null` → persist `null`, không throw
- `app/Share/Attributes/File.php` — `empty(path)` coi là không có file
- `app/Admin/Http/Controllers/ComboController.php` — `normalizeFileInput()` trước khi gán cover/icon

## Verify

- [x] Code path: mảng cover không có path → `FileCast::set()` trả `null`
- [ ] Tạo combo chỉ cover vi + tên en (không cover en) → lưu OK (cần Sail)
- [ ] Tạo combo đủ cả 2 locale → lưu OK (cần Sail)
- [ ] `pint` pass (cần Sail)
