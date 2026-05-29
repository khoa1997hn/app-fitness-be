# bug-classifier

> **HIGH RULE**: KHÔNG BỊA mức độ. (Xem `docs/rules/00-core.md`)

## Mục tiêu

Chỉ dùng trong `/fix-bug`. Phân loại bug **dễ** hay **khó** để quyết định tài liệu cần tạo.

## Input

- Mô tả bug do user cung cấp.

## Output

- Quyết định: **DỄ** hoặc **KHÓ**.
- Path thư mục: `docs/specs/<big>/<detail>/bug-<slug>-<YYYY-MM-DD>/`.
- File cần tạo trong thư mục đó (xem rule bên dưới).

## Tài liệu cần đọc

- `docs/templates/bug-report.md.tpl`
- `docs/guides/spec-driven-workflow.md` (chỉ phần layout bug folder)

## Quy trình

1. Đọc mô tả bug. Hỏi user nếu thiếu thông tin reproduce / scope (qua AskUserQuestion).
2. Phân loại:
   - **DỄ** — thỏa MỌI điều kiện:
     - Fix ≤ 1-2 file
     - Không cần migration
     - Không thay đổi luồng nghiệp vụ
     - Nguyên nhân rõ ngay khi đọc code
   - **KHÓ** — vi phạm ≥ 1 điều kiện trên (cần phân tích sâu, sửa nhiều nơi, hoặc thay nghiệp vụ).
3. Xác định `<big>/<detail>` mà bug thuộc về (hỏi user nếu không chắc).
4. Tạo thư mục `bug-<slug>-<YYYY-MM-DD>/`.
5. File tạo:
   - DỄ → chỉ `report.md` (template `bug-report.md.tpl`).
   - KHÓ → đủ `spec.md` + `plan.md` + `task.md` + `report.md` (sẽ điền dần qua các agent kế tiếp).
6. Báo cáo: phân loại + path folder + danh sách file đã tạo.

## Cấm

- CẤM phân loại **DỄ** chỉ vì user muốn fix nhanh — phải đúng tiêu chí.
- CẤM tạo full set file cho bug DỄ (overkill).
