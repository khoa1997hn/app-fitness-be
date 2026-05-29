# Spec: API tự xóa tài khoản (soft delete)

## Bối cảnh

User (đã đăng nhập) muốn tự xóa tài khoản khỏi hệ thống.
Xóa mềm (`deleted_at`) — admin có thể restore sau.
JWT hiện tại bị invalidate ngay sau khi xóa.
Đăng nhập sau khi đã xóa không được (SoftDeletes loại khỏi query mặc định).

> Persona: end-user đã đăng nhập (JWT).

## Ghi chú gốc từ user (raw, không xóa)

- api tự xóa bản thân (thoát khỏi hệ thống, xóa mềm)
- cần cancel subscription đang active khi xóa user

## Phạm vi

### In-scope
- Migration: thêm `deleted_at` vào `users`.
- `User` model: thêm `SoftDeletes`.
- `DELETE /api/v1/auth/me` — soft-delete user hiện tại + invalidate JWT.

### Out-of-scope
- Xác nhận mật khẩu trước khi xóa (không cần — JWT đã xác thực).
- Xóa dữ liệu liên quan (lesson_favorites, subscriptions records) — giữ nguyên trong DB.
- Admin restore UI (admin có thể restore bằng tool riêng, ngoài scope spec này).
- Outbound cancel/revoke API tới Apple/Google — **không hỗ trợ** (Imdhemy chỉ xử lý receipt verification & webhook đến; Google `purchases.subscriptions.cancel` và Apple `Cancel a Subscription` API yêu cầu tự build HTTP client + OAuth/JWT signing ngoài Imdhemy). Provider tự xử lý gia hạn theo chu kỳ riêng. *(Đã research + xác nhận với user)*

## Nghiệp vụ

- User gọi `DELETE /api/v1/auth/me` với JWT hợp lệ.
- Hệ thống (theo thứ tự): invalidate JWT → cancel subscription đang active (nếu có) → soft-delete user.
- "Cancel subscription" = gọi `SubscriptionService::cancel()` → set `status=cancelled`, `cancelled_at=now()`, `auto_renew=false` trong DB. Áp dụng cho subscription có status `trial`, `active`, hoặc `grace_period`.
- Sau đó user không thể đăng nhập lại (record bị loại khỏi query bởi SoftDeletes).
- Token đã bị invalidate → 401 cho các request tiếp theo.
- Không có subscription → bỏ qua bước cancel, vẫn xóa bình thường.

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
- [ ] Nếu user có valid subscription → sau khi xóa subscription có `status=cancelled`, `cancelled_at` set.
- [ ] Không có subscription → xóa bình thường, không lỗi.
- [ ] JWT bị invalidate ngay — gọi lại với token cũ → 401.
- [ ] Đăng nhập với email đã xóa → 401 (không tìm được user).
- [ ] Record subscriptions / lesson_favorites vẫn còn trong DB.
- [ ] Không token → 401.

## Quyết định (chốt qua ASK)

- **Endpoint**: `DELETE /api/v1/auth/me` (trong nhóm auth, auth:api).
- **Xác nhận mật khẩu**: không cần.
- **Subscription active**: cancel trong DB trước khi soft-delete user. *(Update 2026-05-29)*
- **Cancel scope**: subscription status `trial` + `active` + `grace_period` (= `validSubscription`). *(Update 2026-05-29)*
- **Cancel method**: `SubscriptionService::cancel()` — DB-only; không gọi outbound Apple/Google API. Imdhemy không hỗ trợ outbound cancel; đã research và user xác nhận chấp nhận phương án này. *(Update 2026-05-29)*
- **Dữ liệu liên quan**: records vẫn giữ trong DB (chỉ soft-delete user + cancel subscription).
- **JWT sau xóa**: invalidate (gọi `JWTAuth::invalidate` trước khi delete).
- **Đăng nhập sau xóa**: không được (SoftDeletes mặc định).
- **Response**: `data: null`.

## Liên quan

- `app/Share/Models/User.php`
- `app/Web/Http/Controllers/API/V1/Auth/AuthController.php` hoặc `ProfileController.php`
- `database/migrations/0001_01_01_000000_create_users_table.php`
