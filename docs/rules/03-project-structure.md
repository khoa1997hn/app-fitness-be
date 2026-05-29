# Cấu trúc project

Trong `app/` chỉ có 3 folder: **Admin**, **Web**, **Share**.

## Share

Mọi thứ dùng chung giữa Admin và Web.

- `app/Share/Models/` — TẤT CẢ Model đặt ở đây. Admin/Web KHÔNG có Model riêng.
- `app/Share/Services/` — chỉ chứa service tái sử dụng/phức tạp.
- `app/Share/Utils/` — utilities dùng chung.
- `app/Share/Exceptions/` — domain exceptions.
- `app/Share/Providers/` — Service Providers dùng chung.
- `app/Share/Enums/` — Enum dùng chung.
- `app/Share/Casts/`, `app/Share/Attributes/`, `app/Share/Listeners/` — theo nhu cầu.

## Admin

MVC với Laravel Blade.

- Controllers: `app/Admin/Http/Controllers/`
- Requests: `app/Admin/Http/Requests/`
- Views: `resources/views/admin/`
- Routes: `routes/admin.php` (prefix `/admin`)

Xem thêm `docs/rules/05-admin-blade.md`.

## Web

API trả JSON.

- Controllers: `app/Web/Http/Controllers/`
- Requests: `app/Web/Http/Requests/`
- Routes: `routes/api.php`

Xem thêm `docs/rules/04-api-response.md` và `docs/rules/08-swagger.md`.
