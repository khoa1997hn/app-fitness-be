# Task: Program đang học dở (Left Off)

> Checklist atomic. Mỗi task ≤ 1 đơn vị code (1 file / 1 method / 1 migration).

## Pha pre-design

- [x] solution-reviewer: no proposal from user
- [x] api-designer: section "API Design" trong spec.md đã chốt

## Pha 1 — Service method

- [x] Thêm method `leftOffProgram(User $user): ?array` vào `VideoWatchProgressService`

## Pha 2 — Controller + Route

- [x] Tạo `app/Web/Http/Controllers/API/V1/ProgramLeftOffController.php`
- [x] Thêm route `GET api/v1/programs/left-off` vào `routes/api.php`

## Pha OpenAPI

- [x] openapi-writer: thêm `#[OA\...]` annotation cho `GET /api/v1/programs/left-off`
- [x] `php artisan l5-swagger:generate` chạy thành công

## Pha review

- [x] reviewer-rules pass
- [x] reviewer-smell pass
- [x] reviewer-security pass
- [x] reviewer-duplicate pass + fix

## Pha cleanup

- [x] cleaner: file rác đã xóa
- [x] cleaner: code rác (import/biến/method 0-reference) đã xóa
- [x] cleaner: `.env` ↔ `.env.example` đồng bộ key

## Pha docs sync

- [x] docs-syncer: `project-overview.md` đã reflect module mới

## Pha finalize

- [x] Chạy `php artisan l5-swagger:generate`
- [x] Chạy `pint`
- [x] Verify thủ công các bước trong `plan.md`
- [ ] STOP — hỏi user commit/push

## Update 2026-05-30 — Array response

- [ ] Rewrite `VideoWatchProgressService::leftOffProgram` → trả `array` (nhiều program)
- [ ] Update `ProgramLeftOffController` → response là array, OpenAPI annotation đổi `data` thành array
- [ ] `php artisan l5-swagger:generate`
- [ ] `pint`
