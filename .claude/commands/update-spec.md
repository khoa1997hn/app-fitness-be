---
description: Sửa lại spec đã có (16 vai trò, spec-analyzer/planner/task-breaker chạy mode=update)
---

# /update-spec

Sửa lại spec đã có (sau khi đã `/implement-spec` xong).

Đọc [`docs/commands/update-spec.md`](../../docs/commands/update-spec.md) để hiểu đầy đủ.

## Khác biệt so với /implement-spec

3 vai trò đầu hành xử khác:

### spec-analyzer (mode=update)
- BẮT BUỘC TÌM spec hiện có. KHÔNG tạo file mới.
- User truyền path → dùng. Không truyền → tìm trong `docs/specs/` HOẶC dựa context chat. Không chắc → hỏi user.
- Edit spec.md hiện có: append/sửa section liên quan. KHÔNG xóa lịch sử.

### planner (mode=append) + task-breaker (mode=append)
- KHÔNG ghi đè `plan.md` / `task.md` cũ.
- Append section `## Update <YYYY-MM-DD>` với nội dung mới.

## Chuỗi 16 vai trò

| # | Subagent | Ghi chú |
|---|---|---|
| 1 | `spec-analyzer` | mode=update |
| 2 | `question-asker` | |
| 3 | `solution-reviewer` | skip nếu user chưa propose solution |
| 4 | `api-designer` | |
| 5 | `api-analyzer` | |
| 6 | `planner` | mode=append |
| 7 | `task-breaker` | mode=append |
| 8 | `implementer` | |
| 9 | `openapi-writer` | skip nếu không chạm Web V1 |
| 10 | `reviewer-rules` | |
| 11 | `reviewer-smell` | |
| 12 | `reviewer-security` | |
| 13 | `reviewer-duplicate` | |
| 14 | `cleaner` | |
| 15 | `docs-syncer` | |
| 16 | `finalizer` | HỎI commit/push |

## Ràng buộc

- HIGH RULE: KHÔNG BỊA. Xem [`docs/rules/00-core.md`](../../docs/rules/00-core.md).
- KHÔNG xóa quyết định cũ trong section "Quyết định" của spec.md. Chỉ append.
- KHÔNG tự commit/push.
