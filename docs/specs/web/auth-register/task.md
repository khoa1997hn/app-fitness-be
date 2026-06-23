# Task: API đăng ký tài khoản

## Implement ban đầu (đã hoàn thành)

- [x] `RegistrationController@register`
- [x] Route `POST auth/register`
- [x] OpenAPI attributes

## Update 2026-06-23

- [x] spec-analyzer: retroactive spec + append update section
- [x] question-asker: không còn mơ hồ (user yêu cầu rõ)
- [x] api-designer: append API Design (không endpoint mới)
- [x] api-analyzer: chỉ `RegistrationController`, không migration
- [x] planner: append section Update 2026-06-23
- [x] task-breaker: append checklist này
- [x] Validation `dob` → `nullable|date:Y-m-d`
- [x] Create user: `dob` fallback `null`
- [x] OpenAPI: bỏ `dob` khỏi required, nullable
- [x] openapi-writer: cập nhật OA attributes (regenerate api-docs cần Sail chạy)
- [x] reviewer-rules pass
- [x] reviewer-smell pass
- [x] reviewer-security pass
- [x] reviewer-duplicate pass
- [x] cleaner pass (không có dead code)
- [x] docs-syncer pass
- [ ] finalizer: pint + summary (Sail/Docker chưa chạy)
