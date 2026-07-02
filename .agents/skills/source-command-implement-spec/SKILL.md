---
name: "source-command-implement-spec"
description: "Triển khai feature MỚI theo spec-driven workflow (16 vai trò)"
---

# source-command-implement-spec

Use this skill when the user asks to run the migrated source command `implement-spec`.

## Command Template

# /implement-spec

Triển khai feature mới theo spec-driven workflow.

Đọc [`docs/commands/implement-spec.md`](../../docs/commands/implement-spec.md) để hiểu đầy đủ.

## RULE HIGH (bắt buộc tuyệt đối)

1. **TUYỆT ĐỐI KHÔNG BỊA** — Cấm tự suy diễn nghiệp vụ, field, validation, endpoint, hành vi FE… không có trong spec/yêu cầu user.
2. **LUÔN HỎI TRƯỚC KHI CODE** — Spec mơ hồ, thiếu input, có ≥ 2 cách hiểu → **BẮT BUỘC** hỏi user qua `question-asker`. **CẤM** tự đoán rồi implement.
3. **CÒN `TODO(ask)` → CHƯA ĐƯỢC QUA `implementer`**.
4. Chi tiết: [`docs/rules/00-core.md`](../../docs/rules/00-core.md), [`docs/guides/ask-protocol.md`](../../docs/guides/ask-protocol.md).

## Bạn (Codex) PHẢI làm

Chạy tuần tự **16 vai trò** sau. Mỗi vai trò spawn qua Agent tool với subagent type tương ứng (xem `.Codex/agents/<name>.md`). Hoàn thành Output của vai trò trước rồi mới chuyển vai tiếp theo.

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

- Tuân thủ **RULE HIGH** ở trên — ưu tiên cao nhất.
- KHÔNG skip vai trò.
- KHÔNG tự `git commit` / `git push`. Finalizer chỉ HỎI, user duyệt mới làm.

## Báo cáo cuối

- Path spec.md / plan.md / task.md
- Tóm tắt thay đổi (file, endpoint, migration)
- Kết quả từng reviewer
- Đề xuất commit message
