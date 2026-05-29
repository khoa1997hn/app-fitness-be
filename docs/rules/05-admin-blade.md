# Admin Blade

## Ngôn ngữ

- TẤT CẢ label, message, button, validation text trong Admin **dùng tiếng Việt**.
- KHÔNG cần đa ngôn ngữ cho Admin.

## Template Dashcode

- HTML mẫu nằm trong `resources/dashcode/` — xây Blade theo mẫu này.
- CSS/JS/asset đã có sẵn ở `public/dashcode/`.
- Gọi asset qua helper: `asset('dashcode/css/...')`, `asset('dashcode/js/...')`.

## Tổ chức view

- Layout chung: `resources/views/admin/layouts/`
- Component tái dùng: `resources/views/admin/components/`
- Trang feature: `resources/views/admin/<feature>/index.blade.php`, `create.blade.php`, …

## Route

- File: `routes/admin.php`
- Prefix: `/admin`
- Name pattern: `admin.<feature>.<action>` (ví dụ `admin.users.index`).
