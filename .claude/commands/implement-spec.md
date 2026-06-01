---
description: Triển khai feature MỚI theo spec-driven workflow (16 vai trò)
---

# /implement-spec

Triển khai feature mới theo spec-driven workflow.

Đọc [`docs/commands/implement-spec.md`](../../docs/commands/implement-spec.md) để hiểu đầy đủ.

## Bạn (Claude) PHẢI làm

Chạy tuần tự **16 vai trò** sau. Mỗi vai trò spawn qua Agent tool với subagent type tương ứng (xem `.claude/agents/<name>.md`). Hoàn thành Output của vai trò trước rồi mới chuyển vai tiếp theo.

| # | Subagent | Mô tả ngắn |
|---|---|---|
| 1 | `spec-analyzer` | Đọc/tạo spec.md |
| 2 | `question-asker` | Quét spec, hỏi user mọi điểm mơ hồ |
| 3 | `solution-reviewer` *(skip nếu user chưa propose solution)* | Review solution/DB design propose |
| 4 | `api-designer` | Đề xuất endpoint design |
| 5 | `api-analyzer` | Liệt kê endpoint/migration/model bị ảnh hưởng |
| 6 | `planner` | Viết plan.md |
| 7 | `task-breaker` | Viết task.md checklist |
| 8 | `implementer` | Code theo task.md |
| 9 | `openapi-writer` *(skip nếu không chạm Web V1)* | Viết Swagger annotation |
| 10 | `reviewer-rules` | Review theo docs/rules/ |
| 11 | `reviewer-smell` | Review code smell |
| 12 | `reviewer-security` | Review bảo mật |
| 13 | `reviewer-duplicate` | Tìm + fix duplicate |
| 14 | `cleaner` | Dọn rác (file/import/biến/env/route/view) |
| 15 | `docs-syncer` | Update project-overview + rules nếu state đổi |
| 16 | `finalizer` | Migration + pint + summary + HỎI commit/push |

## Ràng buộc

- HIGH RULE: KHÔNG BỊA. Mơ hồ → AskUserQuestion. Xem [`docs/rules/00-core.md`](../../docs/rules/00-core.md).
- KHÔNG skip vai trò.
- KHÔNG tự `git commit` / `git push`. Finalizer chỉ HỎI, user duyệt mới làm.

## Báo cáo cuối

- Path spec.md / plan.md / task.md
- Tóm tắt thay đổi (file, endpoint, migration)
- Kết quả từng reviewer
- Đề xuất commit message
