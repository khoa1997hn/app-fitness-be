# /implement-spec

Triển khai feature MỚI theo spec-driven workflow. Đọc `docs/commands/implement-spec.md` để hiểu đầy đủ.

## RULE HIGH (bắt buộc tuyệt đối)

1. **TUYỆT ĐỐI KHÔNG BỊA** — Cấm tự suy diễn nghiệp vụ, field, validation, endpoint, hành vi FE… không có trong spec/yêu cầu user.
2. **LUÔN HỎI TRƯỚC KHI CODE** — Spec mơ hồ, thiếu input, có ≥ 2 cách hiểu → **BẮT BUỘC** `AskUserQuestion`. **CẤM** tự đoán rồi implement.
3. **CÒN `TODO(ask)` → CHƯA ĐƯỢC QUA `implementer`**.
4. Chi tiết: [`docs/rules/00-core.md`](../../docs/rules/00-core.md), [`docs/guides/ask-protocol.md`](../../docs/guides/ask-protocol.md).

## Bạn (Cursor) PHẢI làm

Chạy tuần tự 16 vai trò sau. Với MỖI vai trò:
1. Đọc file prompt template tương ứng.
2. Đóng vai theo đúng mô tả trong file đó (Mục tiêu / Input / Output / Quy trình / Cấm).
3. Hoàn thành Output rồi mới chuyển vai tiếp theo.

| # | Vai trò | Prompt template |
|---|---|---|
| 1 | spec-analyzer | [`docs/agents/spec-analyzer.md`](../../docs/agents/spec-analyzer.md) |
| 2 | question-asker | [`docs/agents/question-asker.md`](../../docs/agents/question-asker.md) |
| 3 | solution-reviewer *(skip nếu user chưa propose solution)* | [`docs/agents/solution-reviewer.md`](../../docs/agents/solution-reviewer.md) |
| 4 | api-designer | [`docs/agents/api-designer.md`](../../docs/agents/api-designer.md) |
| 5 | api-analyzer | [`docs/agents/api-analyzer.md`](../../docs/agents/api-analyzer.md) |
| 6 | planner | [`docs/agents/planner.md`](../../docs/agents/planner.md) |
| 7 | task-breaker | [`docs/agents/task-breaker.md`](../../docs/agents/task-breaker.md) |
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
- KHÔNG skip vai trò.
- KHÔNG tự `git commit` / `git push`. Finalizer chỉ HỎI, user duyệt mới làm.
- Mỗi vai trò chỉ đọc rule/guide LIÊN QUAN tới vai trò đó (xem trong từng file agent).

## Báo cáo cuối

Trả về cho user:
- Path spec.md / plan.md / task.md
- Tóm tắt thay đổi (file, endpoint, migration)
- Kết quả từng reviewer
- Đề xuất commit message
