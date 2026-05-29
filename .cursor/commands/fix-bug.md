# /fix-bug

Fix bug. Đọc `docs/commands/fix-bug.md` để hiểu đầy đủ.

## Bạn (Cursor) PHẢI làm

1. **Đầu tiên** chạy `bug-classifier` → xác định bug **DỄ** hay **KHÓ**, tạo folder `docs/specs/<big>/<detail>/bug-<slug>-<YYYY-MM-DD>/`.
2. Theo phân loại, chạy chuỗi vai trò tương ứng.

## Nhánh DỄ

Chỉ tạo `report.md`. Chuỗi:

| # | Vai trò |
|---|---|
| 1 | [`bug-classifier`](../../docs/agents/bug-classifier.md) |
| 2 | [`implementer`](../../docs/agents/implementer.md) |
| 3 | [`openapi-writer`](../../docs/agents/openapi-writer.md) *(skip nếu không chạm Web V1)* |
| 4 | [`reviewer-rules`](../../docs/agents/reviewer-rules.md) |
| 5 | [`reviewer-smell`](../../docs/agents/reviewer-smell.md) |
| 6 | [`reviewer-security`](../../docs/agents/reviewer-security.md) |
| 7 | [`reviewer-duplicate`](../../docs/agents/reviewer-duplicate.md) |
| 8 | [`cleaner`](../../docs/agents/cleaner.md) |
| 9 | [`finalizer`](../../docs/agents/finalizer.md) — kèm cập nhật `report.md` (Cách fix + Files đã sửa + Verify) |

## Nhánh KHÓ

Tạo đủ `spec.md` + `plan.md` + `task.md` + `report.md`. Chuỗi:

| # | Vai trò |
|---|---|
| 1 | [`bug-classifier`](../../docs/agents/bug-classifier.md) |
| 2 | [`spec-analyzer`](../../docs/agents/spec-analyzer.md) (tạo trong folder bug) |
| 3 | [`question-asker`](../../docs/agents/question-asker.md) |
| 4 | [`api-analyzer`](../../docs/agents/api-analyzer.md) |
| 5 | [`planner`](../../docs/agents/planner.md) |
| 6 | [`task-breaker`](../../docs/agents/task-breaker.md) |
| 7 | [`implementer`](../../docs/agents/implementer.md) |
| 8 | [`openapi-writer`](../../docs/agents/openapi-writer.md) *(skip nếu không chạm Web V1)* |
| 9 | [`reviewer-rules`](../../docs/agents/reviewer-rules.md) |
| 10 | [`reviewer-smell`](../../docs/agents/reviewer-smell.md) |
| 11 | [`reviewer-security`](../../docs/agents/reviewer-security.md) |
| 12 | [`reviewer-duplicate`](../../docs/agents/reviewer-duplicate.md) |
| 13 | [`cleaner`](../../docs/agents/cleaner.md) |
| 14 | [`finalizer`](../../docs/agents/finalizer.md) — kèm cập nhật `report.md` |

## Ràng buộc

- HIGH RULE: KHÔNG BỊA. ([`docs/rules/00-core.md`](../../docs/rules/00-core.md))
- Slug bug: kebab-case ngắn gọn. Ví dụ `login-redirect-loop-2026-05-29`.
- `report.md` PHẢI được điền đủ "Cách fix" + "Files đã sửa" + "Verify" trước khi finalizer hỏi commit.
- KHÔNG tự commit/push.
