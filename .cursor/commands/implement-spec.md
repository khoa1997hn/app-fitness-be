# /implement-spec

Triển khai feature MỚI theo spec-driven workflow. Đọc `docs/commands/implement-spec.md` để hiểu đầy đủ.

## Bạn (Cursor) PHẢI làm

Chạy tuần tự 14 vai trò sau. Với MỖI vai trò:
1. Đọc file prompt template tương ứng.
2. Đóng vai theo đúng mô tả trong file đó (Mục tiêu / Input / Output / Quy trình / Cấm).
3. Hoàn thành Output rồi mới chuyển vai tiếp theo.

| # | Vai trò | Prompt template |
|---|---|---|
| 1 | spec-analyzer | [`docs/agents/spec-analyzer.md`](../../docs/agents/spec-analyzer.md) |
| 2 | question-asker | [`docs/agents/question-asker.md`](../../docs/agents/question-asker.md) |
| 3 | api-analyzer | [`docs/agents/api-analyzer.md`](../../docs/agents/api-analyzer.md) |
| 4 | planner | [`docs/agents/planner.md`](../../docs/agents/planner.md) |
| 5 | task-breaker | [`docs/agents/task-breaker.md`](../../docs/agents/task-breaker.md) |
| 6 | implementer | [`docs/agents/implementer.md`](../../docs/agents/implementer.md) |
| 7 | openapi-writer *(skip nếu không chạm Web V1)* | [`docs/agents/openapi-writer.md`](../../docs/agents/openapi-writer.md) |
| 8 | reviewer-rules | [`docs/agents/reviewer-rules.md`](../../docs/agents/reviewer-rules.md) |
| 9 | reviewer-smell | [`docs/agents/reviewer-smell.md`](../../docs/agents/reviewer-smell.md) |
| 10 | reviewer-security | [`docs/agents/reviewer-security.md`](../../docs/agents/reviewer-security.md) |
| 11 | reviewer-duplicate | [`docs/agents/reviewer-duplicate.md`](../../docs/agents/reviewer-duplicate.md) |
| 12 | cleaner | [`docs/agents/cleaner.md`](../../docs/agents/cleaner.md) |
| 13 | docs-syncer | [`docs/agents/docs-syncer.md`](../../docs/agents/docs-syncer.md) |
| 14 | finalizer | [`docs/agents/finalizer.md`](../../docs/agents/finalizer.md) |

## Ràng buộc

- HIGH RULE: KHÔNG BỊA. Mơ hồ → AskUserQuestion. ([`docs/rules/00-core.md`](../../docs/rules/00-core.md))
- KHÔNG skip vai trò.
- KHÔNG tự `git commit` / `git push`. Finalizer chỉ HỎI, user duyệt mới làm.
- Mỗi vai trò chỉ đọc rule/guide LIÊN QUAN tới vai trò đó (xem trong từng file agent).

## Báo cáo cuối

Trả về cho user:
- Path spec.md / plan.md / task.md
- Tóm tắt thay đổi (file, endpoint, migration)
- Kết quả từng reviewer
- Đề xuất commit message
