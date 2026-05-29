# Task: <Tiêu đề feature>

> Checklist atomic. Mỗi task ≤ 1 đơn vị code (1 file / 1 method / 1 migration). Tick khi xong.

## Pha 1 — <Tên>

- [ ] <Task 1>
- [ ] <Task 2>

## Pha 2 — <Tên>

- [ ] <Task 3>
- [ ] <Task 4>

## Pha OpenAPI (nếu chạm endpoint Web V1)

- [ ] openapi-writer cập nhật `#[OA\...]` attribute khớp mapping field
- [ ] `php artisan l5-swagger:generate` chạy thành công

## Pha review

- [ ] reviewer-rules pass
- [ ] reviewer-smell pass
- [ ] reviewer-security pass
- [ ] reviewer-duplicate pass + fix

## Pha cleanup

- [ ] cleaner: file rác đã xóa
- [ ] cleaner: code rác (import/biến/method 0-reference) đã xóa
- [ ] cleaner: `.env` ↔ `.env.example` đồng bộ key
- [ ] cleaner: route / view / translation rác đã xóa hoặc đã hỏi user

## Pha docs sync

- [ ] docs-syncer: `project-overview.md` đã reflect module mới (nếu có)
- [ ] docs-syncer: stack đã reflect package mới (nếu có)
- [ ] docs-syncer: rules / guides / agents đã đồng bộ (user đã duyệt thay đổi interpretive)

## Pha finalize

- [ ] Chạy migration (nếu có)
- [ ] Chạy `pint`
- [ ] Verify thủ công các bước trong `plan.md`
- [ ] STOP — hỏi user commit/push
