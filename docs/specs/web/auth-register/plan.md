# Plan: API đăng ký tài khoản

## Pha 1 — Controller + route (đã hoàn thành)

- `RegistrationController@register` với validation + create user.
- Route `POST auth/register` trong nhóm public (không auth middleware).

## Pha 2 — OpenAPI (đã hoàn thành)

- PHP 8 Attributes trên `RegistrationController`.

## Update 2026-06-23

### Mục tiêu
Làm `phone` và `dob` optional khi đăng ký — chỉ sửa validation + OpenAPI, không migration.

### Pha 1 — Validation
- File: `app/Web/Http/Controllers/API/V1/Auth/RegistrationController.php`
- Đổi `dob` rule: `required|date:Y-m-d` → `nullable|date:Y-m-d`
- Create: `'dob' => $validated['dob'] ?? null`

### Pha 2 — OpenAPI
- Bỏ `dob` khỏi `required` array trong `#[OA\Post]`
- Thêm `nullable: true` cho property `dob`
- Regenerate: `php artisan l5-swagger:generate`

### Verify
- Register không gửi phone/dob → 201
- Register gửi phone/dob → 201
