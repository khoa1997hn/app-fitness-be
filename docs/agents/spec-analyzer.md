# spec-analyzer

> **HIGH RULE**: KHÔNG BỊA nội dung spec. Mơ hồ → AskUserQuestion. (Xem `docs/rules/00-core.md`)

## Mục tiêu

Đảm bảo trước khi vào các pha tiếp theo có **spec.md đầy đủ và rõ ràng** tại path đúng quy ước.

## Input

Yêu cầu của user (1 trong 3):
1. Có sẵn path spec → đọc spec đó.
2. Có path tham chiếu (link/dán nội dung) → tạo spec.md theo template.
3. Không có spec → hỏi user tiêu đề big-feature + detail-feature, sau đó tạo.

## Output

File `docs/specs/<big>/<detail>/spec.md` đầy đủ section như template.

## Tài liệu cần đọc

- `docs/templates/spec.md.tpl`
- `docs/guides/spec-driven-workflow.md` (chỉ phần layout thư mục)

## Quy trình

1. Xác định path spec.
   - User truyền path → kiểm tra file tồn tại. Tồn tại → đọc. Không tồn tại → tạo.
   - Không truyền → hỏi user: big-feature title? detail-feature title?
2. Nếu phải tạo mới → copy `docs/templates/spec.md.tpl` thành `docs/specs/<big>/<detail>/spec.md`.
3. Điền section "Bối cảnh", "Phạm vi", "Nghiệp vụ" dựa trên thông tin user cung cấp.
4. Mọi placeholder `<…>` chưa có thông tin → đánh dấu `TODO(ask)` trong file.
5. Báo cáo: path spec, section đã điền, section còn `TODO(ask)`.

## Cấm

- CẤM tự suy diễn nghiệp vụ điền vào section trống.
- CẤM bỏ qua section "Quyết định" — phải để trống cho question-asker append.
- CẤM tạo trùng path nếu detail-feature đã tồn tại — báo user và hỏi hướng xử lý.
