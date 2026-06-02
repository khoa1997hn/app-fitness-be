# Task: Admin quản lý Banner

## Pha 1: Controller + Request + Route

- [x] 1. Tạo `StoreBannerRequest` via artisan (validate description, translations per locale)
- [x] 2. Tạo `UpdateBannerRequest` via artisan (same rules, reuse/extend Store)
- [x] 3. Tạo `BannerController` via artisan với 6 action (index, create, store, edit, update, destroy)
- [x] 4. Thêm route `banners` resource vào `routes/admin.php`

## Pha 2: Views

- [x] 5. Tạo `resources/views/admin/banners/index.blade.php` (list, phân trang, sort id DESC, data locale vi)
- [x] 6. Tạo `resources/views/admin/banners/create.blade.php` (form tạo mới)
- [x] 7. Tạo `resources/views/admin/banners/edit.blade.php` (form sửa, điền sẵn)
- [x] 8. Thêm menu "Banner" vào sidebar `resources/views/admin/components/sidebar.blade.php`
