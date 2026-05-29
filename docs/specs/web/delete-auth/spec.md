# Spec: API tự xóa tài khoản (soft delete)

## Bối cảnh

User (đã đăng nhập) muốn tự xóa tài khoản khỏi hệ thống.
Xóa mềm (`deleted_at`) — admin có thể restore sau.
JWT hiện tại bị invalidate ngay sau khi xóa.
Đăng nhập sau khi đã xóa không được (SoftDeletes loại khỏi query mặc định).

> Persona: end-user đã đăng nhập (JWT).

## Ghi chú gốc từ user (raw, không xóa)

- api tự xóa bản thân (thoát khỏi hệ thống, xóa mềm)

## Phạm vi

### In-scope
- Migration: thêm `deleted_at` vào `users`.
- `User` model: thêm `SoftDeletes`.
- `DELETE /api/v1/auth/me` — soft-delete user hiện tại + invalidate JWT.

### Out-of-scope
- Xác nhận mật khẩu trước khi xóa (không cần — JWT đã xác thực).
- Chặn xóa khi có subscription active (cho phép xóa bình thường).
- Xóa dữ liệu liên quan (lesson_favorites, subscriptions) — giữ nguyên.
- Admin restore UI (admin có thể restore bằng tool riêng, ngoài scope spec này).

## Nghiệp vụ

- User gọi `DELETE /api/v1/auth/me` với JWT hợp lệ.
- Hệ thống: invalidate JWT → soft-delete user (`deleted_at = now()`).
- Sau đó user không thể đăng nhập lại (record bị loại khỏi query bởi SoftDeletes).
- Token đã bị invalidate → 401 cho các request tiếp theo.

## Input / Output

### Input
- `Authorization: Bearer <JWT>` (bắt buộc).
- Không cần body.

### Output (thành công)
```json
{ "success": true, "message": "Tài khoản đã được xóa thành công", "data": null }
```

### Lỗi
- Không token / token sai → 401.

## Acceptance criteria

- [ ] `DELETE /auth/me` với JWT hợp lệ → user có `deleted_at`, trả 200.
- [ ] JWT bị invalidate ngay — gọi lại với token cũ → 401.
- [ ] Đăng nhập với email đã xóa → 401 (không tìm được user).
- [ ] Dữ liệu liên quan (lesson_favorites, subscriptions) vẫn còn trong DB.
- [ ] Không token → 401.

## Quyết định (chốt qua ASK)

- **Endpoint**: `DELETE /api/v1/auth/me` (trong nhóm auth, auth:api).
- **Xác nhận mật khẩu**: không cần.
- **Subscription active**: cho phép xóa.
- **Dữ liệu liên quan**: giữ nguyên (chỉ soft-delete user).
- **JWT sau xóa**: invalidate (gọi `auth()->logout()` trước khi delete).
- **Đăng nhập sau xóa**: không được (SoftDeletes mặc định).
- **Response**: `data: null`.

## Liên quan

- `app/Share/Models/User.php`
- `app/Web/Http/Controllers/API/V1/Auth/AuthController.php` hoặc `ProfileController.php`
- `database/migrations/0001_01_01_000000_create_users_table.php`
