# Docs

Source of truth của bộ kit AI cho dự án. Áp dụng cho Cursor (qua `.cursor/`) và Claude Code (qua `.claude/` — sẽ ánh xạ sau).

## Cấu trúc

| Folder | Nội dung |
|---|---|
| `rules/` | Rule code (kiến trúc, quality, structure...). 9 file nhỏ, mỗi file 1 chủ đề. |
| `guides/` | Quy trình ngang (ASK protocol, code review checklist, commit protocol...). |
| `templates/` | Template trống cho spec/plan/task/bug. |
| `agents/` | Prompt template LLM-agnostic cho từng vai trò (spec-analyzer, planner, ...). |
| `commands/` | Đặc tả 3 workflow chính: `/implement-spec`, `/update-spec`, `/fix-bug`. |
| `specs/` | Workspace cho specs/plans/tasks/bugs. Cấu trúc 2 cấp `<big>/<detail>/`. |

## 3 nguyên tắc cốt lõi

Đọc trước mọi thứ: [`rules/00-core.md`](rules/00-core.md)

1. **KHÔNG BỊA** — mơ hồ thì hỏi.
2. **ASK-FIRST** — dùng `AskUserQuestion` ngay khi có ≥ 2 cách hiểu.
3. **KHÔNG OVERKILL** — code đơn giản nhất hoạt động được.

## 3 workflow

| Command | Mục đích | Doc |
|---|---|---|
| `/implement-spec` | Feature mới | [`commands/implement-spec.md`](commands/implement-spec.md) |
| `/update-spec` | Sửa feature đã có | [`commands/update-spec.md`](commands/update-spec.md) |
| `/fix-bug` | Fix bug | [`commands/fix-bug.md`](commands/fix-bug.md) |

## Ánh xạ tới LLM

- **Cursor**: `.cursor/rules/*.mdc` (point tới `docs/rules/*`) + `.cursor/commands/*.md` (point tới `docs/commands/*`).
- **Claude Code**: `.claude/agents/*` (paste content từ `docs/agents/*.md`).
- **LLM khác**: chỉ cần đọc `docs/` từ root, không phụ thuộc tool.
