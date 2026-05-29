# Agents (LLM-agnostic)

Mỗi file là **prompt template** cho một vai trò chuyên biệt. Cursor commands và Claude Code subagents đều reference vào đây.

## Format mỗi file

```markdown
# <Tên agent>

> **HIGH RULE**: KHÔNG BỊA. Mơ hồ → AskUserQuestion. (Xem `docs/rules/00-core.md`)

## Mục tiêu
<1-2 câu>

## Input
<Cái gì phải có trước khi agent này chạy>

## Output
<Cái gì agent này phải tạo / sửa / báo cáo>

## Tài liệu cần đọc
<Chỉ list rule/guide LIÊN QUAN tới vai trò, không list toàn bộ>

## Quy trình
<Bước 1, 2, 3…>

## Cấm
<Những thứ agent này KHÔNG được làm>
```

## Nguyên tắc

- **Cô lập kiến thức**: mỗi agent chỉ đọc file rule/guide CỦA NÓ. Reviewer-security KHÔNG đọc rule API Swagger. Implementer KHÔNG đọc commit protocol.
- **Output rõ ràng**: phải tạo/sửa file gì, path nào, format gì.
- **Ngắn**: target ≤ 80 dòng/file.

## Danh sách

| Agent | Vai trò |
|---|---|
| `spec-analyzer` | Đọc/tạo spec.md |
| `question-asker` | Quét spec, hỏi user qua AskUserQuestion |
| `api-analyzer` | Liệt kê endpoint/migration/model ảnh hưởng |
| `planner` | Viết plan.md |
| `task-breaker` | Viết task.md checklist |
| `implementer` | Code theo task.md |
| `openapi-writer` | Viết/sửa Swagger annotation cho endpoint Web V1 |
| `reviewer-rules` | Review theo docs/rules/ |
| `reviewer-smell` | Review code smell / dead code |
| `reviewer-security` | Review bảo mật |
| `reviewer-duplicate` | Tìm + fix duplicate |
| `cleaner` | Dọn rác (file/biến/hàm/env/route/view không dùng) |
| `bug-classifier` | Phân loại bug dễ/khó (chỉ /fix-bug) |
| `finalizer` | Migration + pint + summary |
