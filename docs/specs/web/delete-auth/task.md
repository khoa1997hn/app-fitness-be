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
- [ ] STOP — hỏi user commit/push
