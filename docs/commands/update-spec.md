# Command: /update-spec

Sửa lại spec đã có (sau khi đã `/implement-spec` xong).

## RULE HIGH (bắt buộc tuyệt đối)

1. **TUYỆT ĐỐI KHÔNG BỊA** — Cấm tự suy diễn phần update, nghiệp vụ, field, endpoint, hành vi FE… không có trong yêu cầu user hoặc spec hiện có.
2. **LUÔN HỎI TRƯỚC KHI CODE** — Yêu cầu update mơ hồ, thiếu input, có ≥ 2 cách hiểu → **BẮT BUỘC** `AskUserQuestion` qua `question-asker`. **CẤM** tự suy diễn rồi implement.
3. **CẤM GIẢ ĐỊNH FE/CLIENT** — Không được tự cho rằng FE sẽ gọi API thế nào, truyền field gì… trừ khi user nói rõ hoặc spec đã chốt.
4. **CÒN `TODO(ask)` → CHƯA ĐƯỢC QUA `implementer`** — Mọi điểm mơ hồ phải được user trả lời trước.
5. Chi tiết: [`docs/rules/00-core.md`](../rules/00-core.md), [`docs/guides/ask-protocol.md`](../guides/ask-protocol.md).

## Khi nào dùng

- Cần thay đổi nghiệp vụ / API / UI của 1 feature đã code xong.
- User muốn append yêu cầu mới vào feature hiện có.
- KHÔNG dùng cho feature mới hoàn toàn → dùng `/implement-spec`.
- KHÔNG dùng cho bug → dùng `/fix-bug`.

## Input

- Mô tả update từ user.
- Path spec (user truyền) HOẶC auto-detect trong chat đang mở.

## Output

- `spec.md` được cập nhật (append/sửa các section).
- `plan.md` / `task.md` append section `## Update <YYYY-MM-DD>` mới.
- Code đã update + review + format.
- DỪNG trước commit.

## Khác biệt so với `/implement-spec`

### spec-analyzer
- BẮT BUỘC tìm spec hiện có. KHÔNG tạo file mới.
- Nếu user không truyền path → tìm trong `docs/specs/` theo title hoặc dựa vào context chat. Không chắc → hỏi user.
- Edit spec.md hiện có: append/sửa section liên quan tới update. KHÔNG xóa lịch sử trừ khi user yêu cầu.

### planner / task-breaker
- KHÔNG ghi đè plan.md / task.md cũ.
- Append section mới:
  ```markdown
  ## Update <YYYY-MM-DD>
  ...
  ```
- Giữ lịch sử các update trước.

### Các agent còn lại
- Giống `/implement-spec`.

## Chuỗi vai trò

Y hệt `/implement-spec` (16 vai trò, gồm `solution-reviewer` + `api-designer` sau question-asker, `openapi-writer` sau implementer, `cleaner` sau 4 reviewer, `docs-syncer` trước finalizer), chỉ thay đổi hành vi của spec-analyzer + planner + task-breaker như trên.

## Ràng buộc

- Tuân thủ **RULE HIGH** ở đầu file — ưu tiên cao nhất.
- Cùng ràng buộc với `/implement-spec`.
- Đặc biệt: KHÔNG xóa quyết định cũ trong section "Quyết định" của spec.md. Chỉ append.
