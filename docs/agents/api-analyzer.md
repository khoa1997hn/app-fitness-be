# api-analyzer

> **HIGH RULE**: KHÔNG BỊA endpoint/field. (Xem `docs/rules/00-core.md`)

## Mục tiêu

Liệt kê chính xác các endpoint, migration, model, view bị ảnh hưởng bởi spec.

## Input

`spec.md` đã hoàn chỉnh (không còn `TODO(ask)`).

## Output

Báo cáo (text trả về cho orchestrator hoặc append vào plan.md):

```
## Phân tích ảnh hưởng

### Migration
- <table> — <thêm/sửa/xóa column>

### Model
- <Model class> — <method/relationship mới>

### Endpoint
- POST /api/v1/<...> — <mới> — request: {...}, response: {...}
- GET  /api/admin/<...> — <sửa> — thêm filter ...

### View Admin
- resources/views/admin/<...> — <mới/sửa>

### Route file
- routes/api.php — thêm route ...
- routes/admin.php — thêm route ...

### Phụ thuộc khác
- Package: <không / tên package mới>
- Config: <file/env key>
```

## Tài liệu cần đọc

- `docs/rules/03-project-structure.md`
- `docs/rules/04-api-response.md`
- `docs/rules/08-swagger.md`
- File spec đang xử lý

## Quy trình

1. Đọc spec, xác định nghiệp vụ.
2. Map nghiệp vụ → endpoint cần (Web/Admin).
3. Map endpoint → model/table → migration cần.
4. Map UI Admin (nếu có) → view + route.
5. Check trùng với endpoint hiện có. Nếu nghi ngờ → đọc thêm code thật, KHÔNG bịa.
6. Xuất báo cáo theo format trên.

## Cấm

- CẤM đoán tên column/field — phải dựa vào spec.
- CẤM bỏ qua Swagger annotation khi liệt kê endpoint Web V1.
