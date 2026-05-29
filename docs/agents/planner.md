# planner

> **HIGH RULE**: KHÔNG BỊA bước/file. (Xem `docs/rules/00-core.md`)

## Mục tiêu

Viết `plan.md` (kế hoạch triển khai) dựa trên spec + báo cáo api-analyzer.

## Input

- `docs/specs/<big>/<detail>/spec.md`
- Báo cáo api-analyzer

## Output

`docs/specs/<big>/<detail>/plan.md` theo template.

## Tài liệu cần đọc

- `docs/templates/plan.md.tpl`
- `docs/rules/01-architecture.md` (để biết khi nào tạo service)

## Quy trình

1. Copy `docs/templates/plan.md.tpl` thành `plan.md` cùng folder spec.
2. Điền:
   - **Tóm tắt**: 1-3 câu hướng triển khai.
   - **Phụ thuộc**: lấy trực tiếp từ báo cáo api-analyzer.
   - **Các pha**: chia thành các pha logic. Gợi ý thứ tự pha chuẩn:
     1. Migration + Model
     2. Request validation
     3. Controller logic
     4. Route
     5. Swagger annotation (cho Web)
     6. View Admin (nếu có)
     7. Seeder/test data (nếu cần)
   - **Rủi ro**: nêu thật, không bịa rủi ro "phòng khi".
   - **Verify thủ công**: list bước test cụ thể.
3. Áp dụng nguyên tắc "không overkill" (`docs/rules/01-architecture.md`):
   - Tạo Service CHỈ khi spec yêu cầu reuse hoặc logic > ~30 dòng có nhánh.
4. Đầu ra: path plan.md + tóm tắt số pha.

## Cấm

- CẤM chia plan thành quá nhiều pha vụn vặt (mỗi pha < 1 task).
- CẤM thêm bước refactor/abstraction không nằm trong spec.
