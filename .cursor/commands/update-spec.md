# /update-spec

Sửa lại spec đã có (sau khi đã `/implement-spec` xong). Đọc `docs/commands/update-spec.md` để hiểu đầy đủ.

## Bạn (Cursor) PHẢI làm

Chạy tuần tự 11 vai trò giống `/implement-spec`, nhưng 3 vai trò đầu hành xử KHÁC:

### spec-analyzer
- BẮT BUỘC TÌM spec hiện có. KHÔNG tạo mới.
- User truyền path → dùng path đó.
- Không truyền → tìm trong `docs/specs/` theo title HOẶC dựa vào context chat. Không chắc → hỏi user.
- Edit spec.md hiện có: append/sửa section liên quan. KHÔNG xóa lịch sử.

### planner
- KHÔNG ghi đè plan.md cũ.
- Append section mới: `## Update <YYYY-MM-DD>`.

### task-breaker
- KHÔNG ghi đè task.md cũ.
- Append section mới: `## Update <YYYY-MM-DD>`.

### Các vai trò còn lại
Giống `/implement-spec` 100%.

## Chuỗi vai trò

| # | Vai trò | Prompt template |
|---|---|---|
| 1 | spec-analyzer (mode=update) | [`docs/agents/spec-analyzer.md`](../../docs/agents/spec-analyzer.md) |
| 2 | question-asker | [`docs/agents/question-asker.md`](../../docs/agents/question-asker.md) |
| 3 | api-analyzer | [`docs/agents/api-analyzer.md`](../../docs/agents/api-analyzer.md) |
| 4 | planner (mode=append) | [`docs/agents/planner.md`](../../docs/agents/planner.md) |
| 5 | task-breaker (mode=append) | [`docs/agents/task-breaker.md`](../../docs/agents/task-breaker.md) |
| 6 | implementer | [`docs/agents/implementer.md`](../../docs/agents/implementer.md) |
| 7 | openapi-writer *(skip nếu không chạm Web V1)* | [`docs/agents/openapi-writer.md`](../../docs/agents/openapi-writer.md) |
| 8 | reviewer-rules | [`docs/agents/reviewer-rules.md`](../../docs/agents/reviewer-rules.md) |
| 9 | reviewer-smell | [`docs/agents/reviewer-smell.md`](../../docs/agents/reviewer-smell.md) |
| 10 | reviewer-security | [`docs/agents/reviewer-security.md`](../../docs/agents/reviewer-security.md) |
| 11 | reviewer-duplicate | [`docs/agents/reviewer-duplicate.md`](../../docs/agents/reviewer-duplicate.md) |
| 12 | cleaner | [`docs/agents/cleaner.md`](../../docs/agents/cleaner.md) |
| 13 | finalizer | [`docs/agents/finalizer.md`](../../docs/agents/finalizer.md) |

## Ràng buộc

- HIGH RULE: KHÔNG BỊA. ([`docs/rules/00-core.md`](../../docs/rules/00-core.md))
- KHÔNG xóa quyết định cũ trong "Quyết định" của spec.md. Chỉ append.
- KHÔNG tự commit/push.
