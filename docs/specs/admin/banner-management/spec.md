# Spec: Admin quản lý Banner

## Bối cảnh

Admin cần CRUD banner hiển thị trong app. Model `Banner` + `BannerTranslation` đã tồn tại với migration. Chưa có màn hình Admin nào cho banner. Cần thêm list, create, edit, delete (không có detail).

## Phạm vi

### In-scope
- Danh sách banner (phân trang 20/trang, sort `id` DESC)
- Tạo banner mới (create + store)
- Sửa banner (edit + update)
- Xóa banner (hard delete, confirm JS)
- Upload ảnh `image` qua presigned PUT/GET như program cover (per locale)
- Field `description` (chung, không dịch, tối đa 500 ký tự) hiển thị ở list và form
- Field `link_url` (per locale, admin nhập tay URL)
- Field `order` (per locale, integer, thứ tự hiển thị)
- Field `is_active` (per locale, boolean, bật/tắt banner)
- List hiển thị data locale `vi`

### Out-of-scope
- Detail page banner
- Web API cho banner (đã có)
- Soft delete
- Import/export

## Nghiệp vụ

1. Admin vào `/admin/banners` → thấy danh sách banner phân trang (20/trang), sort `id` DESC.
2. Bấm "Thêm banner" → vào trang create → điền form → lưu → redirect về list.
3. Bấm "Sửa" → vào trang edit → chỉnh sửa → lưu → redirect về edit (giữ nguyên trang).
4. Bấm "Xóa" → JS confirm → DELETE → hard delete → redirect về list.
5. Upload ảnh: file input → presigned PUT lên S3 (qua `AdminS3Upload.upload()` JS helper) → lưu metadata vào hidden inputs → submit form.

## Input / Output

### Input (Create / Update)

- `description`: string, nullable, max 500 ký tự (chung, không dịch)
- Per locale (`vi`, `en`):
  - `translations[<locale>][image][path|name|extension|size]`: file metadata từ presigned upload, required với `vi`
  - `translations[<locale>][link_url]`: string, nullable, max 2048
  - `translations[<locale>][order]`: integer, nullable (default 0)
  - `translations[<locale>][is_active]`: boolean (checkbox), default true

### Output (List)

- `id`, `description`, ảnh `image` (locale vi), `link_url` (locale vi), `order` (locale vi), `is_active` (locale vi), thao tác (Sửa / Xóa)

## Acceptance criteria

- [ ] Danh sách banner load đúng, phân trang 20/trang, sort `id` DESC
- [ ] Tạo banner với cả 2 locale → lưu đúng vào `banners` + `banner_translations`
- [ ] Tạo banner chỉ cần locale vi required, en optional → OK
- [ ] Upload ảnh presigned → preview ngay, không reload
- [ ] Sửa banner → dữ liệu cũ được điền sẵn trong form
- [ ] Xóa banner → xóa khỏi DB (kèm `banner_translations` cascade)
- [ ] Validate lỗi hiển thị dưới từng field, không mất dữ liệu đã nhập/upload
- [ ] Menu sidebar có "Banner"
- [ ] Toàn bộ label tiếng Việt

## Quyết định

- 2026-06-02 — Locale hiển thị trong list → vi
- 2026-06-02 — description: không dịch, tối đa 500 ký tự, hiển thị trong list và form, admin nhập tay
- 2026-06-02 — Phân trang list → có, 20 bản ghi/trang
- 2026-06-02 — Sort list → theo `id` DESC

## Liên quan

- Model: `app/Share/Models/Banner.php`, `app/Share/Models/BannerTranslation.php`
- Migration: `database/migrations/2026_01_30_102529_create_banners_table.php`
- Tham khảo: `app/Admin/Http/Controllers/ProgramController.php`, `resources/views/admin/programs/`
