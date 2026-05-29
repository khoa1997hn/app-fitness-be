# Command: /update-spec

Sửa lại spec đã có (sau khi đã `/implement-spec` xong).

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

Y hệt `/implement-spec` (13 vai trò, gồm `openapi-writer` sau implementer và `cleaner` sau 4 reviewer), chỉ thay đổi hành vi của spec-analyzer + planner + task-breaker như trên.

## Ràng buộc

- Cùng ràng buộc với `/implement-spec`.
- Đặc biệt: KHÔNG xóa quyết định cũ trong section "Quyết định" của spec.md. Chỉ append.
