# Task: API chi tiết Lesson

> Checklist atomic. Mỗi task ≤ 1 đơn vị code (1 file / 1 method / 1 migration). Tick khi xong.

## Pha pre-design

- [x] solution-reviewer: skip — user không propose solution riêng
- [x] api-designer: section "API Design" trong spec.md đã được user duyệt

## Pha 1 — Controller + Route

- [x] Tạo `LessonController` với method `show` tại `app/Web/Http/Controllers/API/V1/LessonController.php`
- [x] Đăng ký route `GET lessons/{lesson}` trong `routes/api.php`

## Pha OpenAPI (nếu chạm endpoint Web V1)

- [x] openapi-writer cập nhật `#[OA\...]` attribute khớp mapping field
- [ ] `php artisan l5-swagger:generate` chạy thành công (Sail không có trên host — cần chạy thủ công)

## Pha review

- [x] reviewer-rules pass
- [x] reviewer-smell pass
- [x] reviewer-security pass
- [x] reviewer-duplicate pass + fix (extract `MapsLessonForApi` trait)

## Pha cleanup

- [x] cleaner: file rác đã xóa
- [x] cleaner: code rác (import/biến/method 0-reference) đã xóa
- [x] cleaner: `.env` ↔ `.env.example` đồng bộ key (không thêm env)
- [x] cleaner: route / view / translation rác đã xóa hoặc đã hỏi user

## Pha docs sync

- [x] docs-syncer: `project-overview.md` đã reflect module mới (nếu có)
- [x] docs-syncer: stack đã reflect package mới (nếu có)
- [x] docs-syncer: rules / guides / agents đã đồng bộ (user đã duyệt thay đổi interpretive)

## Pha finalize

- [x] Chạy migration (nếu có) — không có migration
- [ ] Chạy `pint` (Sail không có trên host — cần chạy thủ công)
- [ ] Verify thủ công các bước trong `plan.md`
- [ ] STOP — hỏi user commit/push
