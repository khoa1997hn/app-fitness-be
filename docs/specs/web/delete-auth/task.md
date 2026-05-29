# Task: API tự xóa tài khoản (soft delete)

## Pha pre-design

- [x] spec-analyzer + question-asker: chốt qua ASK
- [x] api-designer: spec.md
- [x] api-analyzer: route không trùng, auth:api

## Pha 1 — DB & Model

- [x] Migration `add_deleted_at_to_users_table` (softDeletes)
- [x] `User` model: thêm `SoftDeletes` + PHPDoc `deleted_at`

## Pha 2 — Controller

- [x] `ProfileController@destroy`: invalidate JWT → soft-delete → 200

## Pha 3 — Route

- [x] `DELETE auth/me` (auth:api)

## Pha OpenAPI

- [x] `#[OA\Delete]` annotation trên `destroy`
- [x] `l5-swagger:generate`

## Pha review

- [x] reviewer-rules pass (field cần dùng; softDeletes đúng spec; không bịa field)
- [x] reviewer-smell pass (method nhỏ, rõ ràng; không global state)
- [x] reviewer-security pass (auth:api; invalidate trước delete; user-scoped; không cần password confirm vì JWT)
- [x] reviewer-duplicate pass (không trùng logic với logout; destroy độc lập)

## Pha cleanup & docs

- [x] cleaner: pint PASS (4 files)
- [x] docs-syncer: `project-overview.md`
- [x] verify: soft-delete OK, withTrashed thấy record, User::find sau delete trả null
- [x] STOP — đã commit/push (4ffccc5)

## Update 2026-05-29 — Cancel subscription khi xóa

- [x] `ProfileController`: inject `SubscriptionService`, cancel `validSubscription` trước khi delete
- [x] reviewer-rules pass (spec-driven; dùng service đã có; không bịa)
- [x] reviewer-smell pass (null-guard trước cancel; constructor injection)
- [x] reviewer-security pass (user-scoped; subscription load từ chính user; không leak)
- [x] reviewer-duplicate pass (tái dùng `SubscriptionService::cancel` + `validSubscription`)
- [x] cleaner: pint PASS (1 file fixed)
- [x] swagger regenerated
- [x] verify: status=cancelled, cancelled_at set, deleted_at set — tất cả OK
- [ ] STOP — hỏi user commit/push
