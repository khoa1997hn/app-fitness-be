# Plan: Hủy subscription phía provider

> Kế hoạch dựa trên `spec.md` cùng folder.

## Tóm tắt

3 thay đổi nhỏ, không có migration, không có endpoint mới:
1. `GoogleService::cancelSubscription()` — gọi Imdhemy `cancel()`.
2. `SubscriptionService::updateSubscription()` — fix null user khi soft-deleted.
3. `ProfileController::destroy()` — đổi order + dùng provider cancel thay vì DB-cancel trực tiếp.

## Phụ thuộc

- Không có migration mới.
- Không có model mới.
- Tái dùng `ImdhemySubscription::googlePlay()->cancel()` đã có trong Imdhemy.

## Các pha

### Pha 1 — GoogleService
- Thêm `cancelSubscription(Subscription $subscription): void`.
- Load `googleSubscription` (item_id, purchase_token) từ relation.
- Gọi `ImdhemySubscription::googlePlay()->id(...)->token(...)->cancel()`.
- Nếu không tìm thấy `googleSubscription` hoặc API fail → throw RuntimeException.

### Pha 2 — SubscriptionService bug fix
- Đổi `$subscription->user->update(...)` thành `$subscription->user()->withTrashed()->first()?->update(...)`.
- Giữ nguyên toàn bộ logic khác.

### Pha 3 — ProfileController
- Remove: inject `SubscriptionService` (không còn cần).
- Add: inject `GoogleService`.
- Đổi order: provider cancel → JWT invalidate → soft-delete.
- Apple: skip + ghi log.

## Verify thủ công

- Gọi `GoogleService::cancelSubscription()` trực tiếp qua tinker với stub subscription.
- Webhook flow: subscription found sau khi user soft-deleted, `updateSubscription()` không crash.
- `DELETE /auth/me` không có subscription → xóa bình thường.

## Update 2026-05-29 — SubscriptionManager

### Tóm tắt
Tạo `SubscriptionManager` bọc provider routing. Controller chỉ inject manager, không biết GoogleService.

### Phụ thuộc
- Class mới: `SubscriptionManager` inject `GoogleService` (constructor)
- Sửa: `ProfileController` đổi inject `GoogleService` → `SubscriptionManager`

### Pha
1. `SubscriptionManager::cancelProvider(Subscription $subscription): void` — switch provider, delegate.
2. `ProfileController::destroy()` — đổi `$this->googleService->...` → `$this->subscriptionManager->cancelProvider(...)`.
