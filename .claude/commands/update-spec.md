---
description: Sửa lại spec đã có (16 vai trò, spec-analyzer/planner/task-breaker chạy mode=update)
---

# /update-spec

Sửa lại spec đã có (sau khi đã `/implement-spec` xong).

Đọc [`docs/commands/update-spec.md`](../../docs/commands/update-spec.md) để hiểu đầy đủ.

## RULE HIGH (bắt buộc tuyệt đối)

1. **TUYỆT ĐỐI KHÔNG BỊA** — Cấm tự suy diễn phần update, nghiệp vụ, field, hành vi FE… không có trong yêu cầu user.
2. **LUÔN HỎI TRƯỚC KHI CODE** — Yêu cầu update mơ hồ, thiếu input, có ≥ 2 cách hiểu → **BẮT BUỘC** hỏi user qua `question-asker`. **CẤM** tự suy diễn rồi implement.
3. **CẤM GIẢ ĐỊNH FE/CLIENT** — Trừ khi user nói rõ hoặc spec đã chốt.
4. **CÒN `TODO(ask)` → CHƯA ĐƯỢC QUA `implementer`**.
5. Chi tiết: [`docs/rules/00-core.md`](../../docs/rules/00-core.md), [`docs/guides/ask-protocol.md`](../../docs/guides/ask-protocol.md).

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

- Tuân thủ **RULE HIGH** ở trên — ưu tiên cao nhất.
- KHÔNG xóa quyết định cũ trong section "Quyết định" của spec.md. Chỉ append.
- KHÔNG tự commit/push.
