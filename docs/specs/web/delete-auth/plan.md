# Plan: API tự xóa tài khoản (soft delete)

> Kế hoạch dựa trên `spec.md` cùng folder.

## Tóm tắt

Thêm soft-delete vào `users` (migration + SoftDeletes trait). Thêm action `destroy` vào `ProfileController` (hoặc `AuthController`). Invalidate JWT rồi soft-delete. Route + OpenAPI.

## Phụ thuộc

- Migration mới: `add_deleted_at_to_users_table`
- Model: `User` thêm `SoftDeletes`
- Controller: thêm method `destroy` vào `ProfileController` (đặt cùng chỗ với `show` profile)
- Route: `DELETE /api/v1/auth/me` (auth:api)
- Không cần service mới, không cần seeder

## Các pha

### Pha 1 — DB & Model
- Migration thêm `softDeletes()` vào `users`
- `User` dùng `SoftDeletes` trait

### Pha 2 — Controller
- `ProfileController@destroy`: `auth()->logout()` → `$user->delete()` → 200

### Pha 3 — Route
- `DELETE auth/me` trong nhóm `auth:api`

### Pha 4 — OpenAPI
- `#[OA\Delete]` annotation + generate

## Verify thủ công

- `DELETE /auth/me` với JWT → 200, `users.deleted_at` set
- Token cũ gọi lại → 401
- Đăng nhập bằng email đã xóa → 401
- `lesson_favorites` của user vẫn còn

## Update 2026-05-29 — Cancel subscription khi xóa

### Tóm tắt
Inject `SubscriptionService` vào `ProfileController@destroy`. Trước khi soft-delete: load `validSubscription`, nếu có thì gọi `$subscriptionService->cancel($subscription)`. Không có thì bỏ qua. Không thay đổi endpoint/response/migration.

### Phụ thuộc
- Service: `SubscriptionService` (đã có, inject qua constructor)
- Relation: `User::validSubscription()` (đã có)

### Verify bổ sung
- User có subscription active → sau khi xóa: `subscriptions.status=cancelled`, `cancelled_at` set
- User không có subscription → xóa bình thường, không lỗi
