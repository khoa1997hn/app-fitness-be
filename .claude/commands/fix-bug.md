---
description: Fix bug — phân loại DỄ/KHÓ trước, chuỗi vai trò khác nhau theo nhánh
---

# /fix-bug

Fix bug. Đọc [`docs/commands/fix-bug.md`](../../docs/commands/fix-bug.md) để hiểu đầy đủ.

## Bước 1 — Phân loại

Đầu tiên spawn `bug-classifier` → xác định bug **DỄ** hay **KHÓ**. Agent này tạo folder `docs/specs/<big>/<detail>/bug-<slug>-<YYYY-MM-DD>/` và file ban đầu (`report.md` cho DỄ, đủ 4 file cho KHÓ).

## Nhánh DỄ — 9 vai trò

Chỉ tạo `report.md`.

| # | Subagent | Ghi chú |
|---|---|---|
| 1 | `bug-classifier` | |
| 2 | `implementer` | fix thẳng |
| 3 | `openapi-writer` | skip nếu không chạm Web V1 |
| 4 | `reviewer-rules` | |
| 5 | `reviewer-smell` | |
| 6 | `reviewer-security` | |
| 7 | `reviewer-duplicate` | |
| 8 | `cleaner` | |
| 9 | `finalizer` | cập nhật `report.md` (Cách fix + Files đã sửa + Verify) + HỎI commit/push |

## Nhánh KHÓ — 17 vai trò

Tạo đủ `spec.md` + `plan.md` + `task.md` + `report.md`.

| # | Subagent | Ghi chú |
|---|---|---|
| 1 | `bug-classifier` | |
| 2 | `spec-analyzer` | tạo trong folder bug |
| 3 | `question-asker` | |
| 4 | `solution-reviewer` | skip nếu user chưa propose solution |
| 5 | `api-designer` | skip nếu fix không đụng endpoint |
| 6 | `api-analyzer` | |
| 7 | `planner` | |
| 8 | `task-breaker` | |
| 9 | `implementer` | |
| 10 | `openapi-writer` | skip nếu không chạm Web V1 |
| 11 | `reviewer-rules` | |
| 12 | `reviewer-smell` | |
| 13 | `reviewer-security` | |
| 14 | `reviewer-duplicate` | |
| 15 | `cleaner` | |
| 16 | `docs-syncer` | |
| 17 | `finalizer` | cập nhật `report.md` + HỎI commit/push |

## Ràng buộc

- HIGH RULE: KHÔNG BỊA. Xem [`docs/rules/00-core.md`](../../docs/rules/00-core.md).
- Slug bug: kebab-case ngắn gọn, không dấu. Ví dụ `login-redirect-loop-2026-05-29`.
- `report.md` PHẢI điền đủ "Cách fix" + "Files đã sửa" + "Verify" trước khi finalizer hỏi commit.
- KHÔNG tự commit/push.
