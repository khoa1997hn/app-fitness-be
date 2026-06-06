# /update-spec

Sửa lại spec đã có (sau khi đã `/implement-spec` xong). Đọc `docs/commands/update-spec.md` để hiểu đầy đủ.

## RULE HIGH (bắt buộc tuyệt đối)

1. **TUYỆT ĐỐI KHÔNG BỊA** — Cấm tự suy diễn phần update, nghiệp vụ, field, hành vi FE… không có trong yêu cầu user.
2. **LUÔN HỎI TRƯỚC KHI CODE** — Yêu cầu update mơ hồ, thiếu input, có ≥ 2 cách hiểu → **BẮT BUỐC** `AskUserQuestion`. **CẤM** tự suy diễn rồi implement.
3. **CẤM GIẢ ĐỊNH FE/CLIENT** — Trừ khi user nói rõ hoặc spec đã chốt.
4. **CÒN `TODO(ask)` → CHƯA ĐƯỢC QUA `implementer`**.
5. Chi tiết: [`docs/rules/00-core.md`](../../docs/rules/00-core.md), [`docs/guides/ask-protocol.md`](../../docs/guides/ask-protocol.md).

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
| 3 | solution-reviewer *(skip nếu user chưa propose solution)* | [`docs/agents/solution-reviewer.md`](../../docs/agents/solution-reviewer.md) |
| 4 | api-designer | [`docs/agents/api-designer.md`](../../docs/agents/api-designer.md) |
| 5 | api-analyzer | [`docs/agents/api-analyzer.md`](../../docs/agents/api-analyzer.md) |
| 6 | planner (mode=append) | [`docs/agents/planner.md`](../../docs/agents/planner.md) |
| 7 | task-breaker (mode=append) | [`docs/agents/task-breaker.md`](../../docs/agents/task-breaker.md) |
| 8 | implementer | [`docs/agents/implementer.md`](../../docs/agents/implementer.md) |
| 9 | openapi-writer *(skip nếu không chạm Web V1)* | [`docs/agents/openapi-writer.md`](../../docs/agents/openapi-writer.md) |
| 10 | reviewer-rules | [`docs/agents/reviewer-rules.md`](../../docs/agents/reviewer-rules.md) |
| 11 | reviewer-smell | [`docs/agents/reviewer-smell.md`](../../docs/agents/reviewer-smell.md) |
| 12 | reviewer-security | [`docs/agents/reviewer-security.md`](../../docs/agents/reviewer-security.md) |
| 13 | reviewer-duplicate | [`docs/agents/reviewer-duplicate.md`](../../docs/agents/reviewer-duplicate.md) |
| 14 | cleaner | [`docs/agents/cleaner.md`](../../docs/agents/cleaner.md) |
| 15 | docs-syncer | [`docs/agents/docs-syncer.md`](../../docs/agents/docs-syncer.md) |
| 16 | finalizer | [`docs/agents/finalizer.md`](../../docs/agents/finalizer.md) |

## Ràng buộc

- Tuân thủ **RULE HIGH** ở trên — ưu tiên cao nhất.
- KHÔNG xóa quyết định cũ trong "Quyết định" của spec.md. Chỉ append.
- KHÔNG tự commit/push.
