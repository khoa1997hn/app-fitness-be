# Admin — Blade + Dashcode + tiếng Việt

## Stack
- Auth: session-based, guard `auth:admin` (KHÔNG dùng `auth:api` của Web).
- View: Laravel Blade. Template Dashcode trong `resources/dashcode/`, asset đã build sẵn ở `public/dashcode/` → `asset('dashcode/...')`.
- Route: `routes/admin.php`, prefix `/admin`, name `admin.<feature>.<action>`.

## Ngôn ngữ
- Mọi label/message/button/validation text **dùng tiếng Việt**. KHÔNG cần đa ngôn ngữ cho Admin.

## Endpoint JSON trong Admin
- Nếu Admin controller trả JSON (ví dụ AJAX action) → dùng `ResponseAPI` y như Web. CẤM `response()->json()` raw.

Chi tiết: [`docs/rules/05-admin-blade.md`](../../docs/rules/05-admin-blade.md).
