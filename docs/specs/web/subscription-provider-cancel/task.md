# Task: Hủy subscription phía provider

## Pha 1 — GoogleService

- [x] `cancelSubscription(Subscription $subscription): void` — gọi ImdhemySubscription::googlePlay()->cancel()

## Pha 2 — SubscriptionService bug fix

- [x] `updateSubscription()`: null-safe user update với withTrashed — webhook không crash khi user soft-deleted

## Pha 3 — ProfileController

- [x] Đổi inject SubscriptionService → GoogleService
- [x] Đổi flow: provider cancel → JWT invalidate → soft-delete
- [x] Apple: skip + log

## Pha OpenAPI

- [x] Cập nhật description `DELETE /auth/me` (mention Google cancel, Apple skip, fail_abort)
- [x] `l5-swagger:generate`

## Pha review

- [x] reviewer-rules pass (spec-driven; tái dùng Imdhemy; không bịa)
- [x] reviewer-smell pass (log đủ; no DB update trực tiếp; method nhỏ)
- [x] reviewer-security pass (provider cancel trước JWT invalidate — nếu fail, user không bị xóa; no info leak)
- [x] reviewer-duplicate pass (tái dùng ImdhemySubscription façade đã dùng ở verifyPurchase)

## Pha cleanup & docs

- [x] cleaner: pint PASS (3 files)
- [x] docs-syncer: project-overview.md
- [x] verify: cancel OK, webhook không crash khi user soft-deleted
- [ ] STOP — hỏi user commit/push

## Update 2026-05-29 — SubscriptionManager

- [ ] Tạo `SubscriptionManager` + `cancelProvider()`
- [ ] ProfileController: đổi inject GoogleService → SubscriptionManager
- [ ] reviewer + pint
- [ ] STOP — hỏi user commit/push
