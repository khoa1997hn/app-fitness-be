# Plan: Admin quản lý Banner

## Phân tích ảnh hưởng

### Migration
- Không cần — `banners` + `banner_translations` đã tồn tại đủ field.

### Model
- `app/Share/Models/Banner.php` — không đổi.
- `app/Share/Models/BannerTranslation.php` — không đổi.

### Controller / Request (Admin)
- `app/Admin/Http/Controllers/BannerController.php` — mới (index, create, store, edit, update, destroy)
- `app/Admin/Http/Requests/StoreBannerRequest.php` — mới
- `app/Admin/Http/Requests/UpdateBannerRequest.php` — mới

### View Admin
- `resources/views/admin/banners/index.blade.php` — mới
- `resources/views/admin/banners/create.blade.php` — mới
- `resources/views/admin/banners/edit.blade.php` — mới
- `resources/views/admin/components/sidebar.blade.php` — thêm menu "Banner"

### Route file
- `routes/admin.php` — thêm resource `banners` (index, create, store, edit, update, destroy)

### Phụ thuộc khác
- Package: không mới
- Config: không mới

## Các pha

### Pha 1: Controller + Request
1. Tạo `BannerController` với 6 action: index, create, store, edit, update, destroy.
2. Tạo `StoreBannerRequest` + `UpdateBannerRequest`.
3. Thêm route resource `banners` vào `routes/admin.php`.

### Pha 2: Views Blade
1. `banners/index.blade.php` — bảng list, phân trang, sort id DESC, data locale vi.
2. `banners/create.blade.php` — form tạo mới (description + per-locale: image upload, link_url, order, is_active).
3. `banners/edit.blade.php` — form sửa (điền sẵn dữ liệu cũ).
4. Thêm "Banner" vào menu sidebar.

## Verify thủ công
- [ ] Danh sách load đúng, phân trang, sort id DESC
- [ ] Tạo banner lưu vào cả 2 bảng
- [ ] Upload ảnh presigned → preview ngay
- [ ] Sửa banner → dữ liệu cũ điền sẵn
- [ ] Xóa → hard delete + cascade translations
- [ ] Validate lỗi hiển thị đúng chỗ
- [ ] Menu sidebar hiện "Banner"
