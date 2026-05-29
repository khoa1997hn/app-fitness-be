# Cấu trúc project

Trong `app/` chỉ có 3 folder: **Admin**, **Web**, **Share**.

## Share — folder CORE (dùng chung)

`app/Share/` là folder **core/shared** của project — chứa MỌI code dùng chung giữa 2 module entry-point: `app/Web/` (API native app) và `app/Admin/` (web blade).

Quy ước: code logic / model / enum / service / exception nào dùng cả 2 nơi → đặt trong Share. Chỉ tách riêng vào `app/Web/` hoặc `app/Admin/` khi thật sự CHỈ 1 module dùng (controller, request, view).

Layout:

- `app/Share/Models/` — **TẤT CẢ Model đặt ở đây**. Admin/Web KHÔNG có Model riêng.
- `app/Share/Enums/` — TẤT CẢ Enum dùng chung (xem `docs/rules/11-enum.md`).
- `app/Share/Services/` — service tái sử dụng / phức tạp (xem `docs/rules/01-architecture.md`).
- `app/Share/Exceptions/` — domain exceptions (xem `docs/rules/02-code-quality.md`).
- `app/Share/Utils/` — utilities dùng chung (ví dụ `ResponseAPI`).
- `app/Share/Providers/` — Service Providers dùng chung.
- `app/Share/Listeners/` — Event listeners (ví dụ subscription webhook).
- `app/Share/Casts/`, `app/Share/Attributes/`, `app/Share/Http/` — theo nhu cầu.

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
