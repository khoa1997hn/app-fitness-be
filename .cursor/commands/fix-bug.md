# /fix-bug

Fix bug. Đọc `docs/commands/fix-bug.md` để hiểu đầy đủ.

## RULE HIGH (bắt buộc tuyệt đối)

1. **TUYỆT ĐỐI KHÔNG BỊA** — Cấm tự suy diễn nguyên nhân bug, hành vi FE/client, field, endpoint… không có trong mô tả user hoặc bằng chứng reproduce thực tế.
2. **LUÔN HỎI TRƯỚC KHI FIX** — Bug report mơ hồ, thiếu reproduce, có ≥ 2 cách hiểu → **BẮT BUỘC** `AskUserQuestion`. **CẤM** tự đoán nguyên nhân rồi sửa.
3. **CẤM GIẢ ĐỊNH FE/CLIENT** — Không được tự cho FE truyền sai field/URL/id… trừ khi user xác nhận.
4. **CHƯA RÕ NGUYÊN NHÂN → CHƯA ĐƯỢC IMPLEMENT** — Kể cả nhánh DỄ.
5. Chi tiết: [`docs/rules/00-core.md`](../../docs/rules/00-core.md), [`docs/guides/ask-protocol.md`](../../docs/guides/ask-protocol.md).

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
| 9 | [`docs-syncer`](../../docs/agents/docs-syncer.md) |
| 10 | [`finalizer`](../../docs/agents/finalizer.md) — kèm cập nhật `report.md` (Cách fix + Files đã sửa + Verify) |

## Nhánh KHÓ

Tạo đủ `spec.md` + `plan.md` + `task.md` + `report.md`. Chuỗi:

| # | Vai trò |
|---|---|
| 1 | [`bug-classifier`](../../docs/agents/bug-classifier.md) |
| 2 | [`spec-analyzer`](../../docs/agents/spec-analyzer.md) (tạo trong folder bug) |
| 3 | [`question-asker`](../../docs/agents/question-asker.md) |
| 4 | [`solution-reviewer`](../../docs/agents/solution-reviewer.md) *(skip nếu user chưa propose solution)* |
| 5 | [`api-designer`](../../docs/agents/api-designer.md) *(skip nếu fix không đụng endpoint)* |
| 6 | [`api-analyzer`](../../docs/agents/api-analyzer.md) |
| 7 | [`planner`](../../docs/agents/planner.md) |
| 8 | [`task-breaker`](../../docs/agents/task-breaker.md) |
| 9 | [`implementer`](../../docs/agents/implementer.md) |
| 10 | [`openapi-writer`](../../docs/agents/openapi-writer.md) *(skip nếu không chạm Web V1)* |
| 11 | [`reviewer-rules`](../../docs/agents/reviewer-rules.md) |
| 12 | [`reviewer-smell`](../../docs/agents/reviewer-smell.md) |
| 13 | [`reviewer-security`](../../docs/agents/reviewer-security.md) |
| 14 | [`reviewer-duplicate`](../../docs/agents/reviewer-duplicate.md) |
| 15 | [`cleaner`](../../docs/agents/cleaner.md) |
| 16 | [`docs-syncer`](../../docs/agents/docs-syncer.md) |
| 17 | [`finalizer`](../../docs/agents/finalizer.md) — kèm cập nhật `report.md` |

## Ràng buộc

- Tuân thủ **RULE HIGH** ở trên — ưu tiên cao nhất.
- Slug bug: kebab-case ngắn gọn. Ví dụ `login-redirect-loop-2026-05-29`.
- `report.md` PHẢI được điền đủ "Cách fix" + "Files đã sửa" + "Verify" trước khi finalizer hỏi commit.
- KHÔNG tự commit/push.
