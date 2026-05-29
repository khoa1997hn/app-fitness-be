# api-designer

> **HIGH RULE**: KHÔNG BỊA endpoint. Endpoint propose phải dựa trên use case TRONG SPEC. Mơ hồ → AskUserQuestion. (Xem `docs/rules/00-core.md`)

## Mục tiêu

Từ spec đã clear (và solution đã review), **đề xuất design các endpoint API** cho user duyệt: method/path/request/response/auth/status codes. Khác với `api-analyzer` (liệt kê impact), `api-designer` PROPOSE design.

## Khi nào chạy

- SAU `solution-reviewer`.
- TRƯỚC `api-analyzer` (api-analyzer dùng design đã chốt để liệt kê impact).
- Áp dụng cho `/implement-spec`, `/update-spec`, `/fix-bug` nhánh KHÓ.

## Input

- `docs/specs/<big>/<detail>/spec.md` (hoàn chỉnh).
- Quyết định solution từ pha trước.

## Output

- Section "API Design" được append vào `spec.md`:
  ```markdown
  ## API Design

  ### POST /api/v1/...
  - **Auth**: required (Bearer)
  - **Request**: { ... }
  - **Response 200**: { ... }
  - **Errors**: 401, 422
  ```
- User đã duyệt design qua AskUserQuestion.
- Tick `[x]` "api-designer pass" trong task.md.

## Tài liệu cần đọc

- `docs/rules/04-api-response.md` (format response)
- `docs/rules/08-swagger.md` (status codes chuẩn, attribute style)
- `docs/rules/03-project-structure.md` (Web V1 vs Admin)
- `docs/rules/11-enum.md` (response enum không dùng `->value`)
- `docs/rules/14-translatable.md` (header `x-locale`)
- `docs/project-overview.md` (entity nào tồn tại, naming convention)

## Quy trình

1. Đọc spec.md. Trích các use case cần endpoint:
   - User list / get detail / create / update / delete.
   - Action nghiệp vụ (purchase, cancel, redeem, …).
   - Webhook (nếu có).
2. Với mỗi use case → propose:
   - **HTTP method** (REST: GET list, GET detail, POST create, PUT/PATCH update, DELETE).
   - **Path**: kebab-case, số nhiều cho collection (`/lessons`), `:id` cho detail (`/lessons/{id}`).
   - **Auth**: Bearer JWT? Public?
   - **Request body** (cho POST/PUT/PATCH): list field + type + validation rule (lấy từ spec).
   - **Response 200/201**: shape JSON theo `ResponseAPI::success([...])`, map field rõ ràng.
   - **Status codes**: 200/201, 400, 401, 403, 404, 422, 500.
3. Áp dụng convention dự án:
   - Endpoint cần phân quyền theo subscription plan (Basic / Plus / All) → thêm note "Gate by plan".
   - Endpoint trả entity multi-language → response field theo locale từ header `x-locale`.
   - Field enum trong response → trả thẳng (không `->value`).
4. So sánh với endpoint hiện có để tránh trùng (đọc `app/Web/Http/Controllers/API/V1/`).
5. Gọi `AskUserQuestion` cho mỗi endpoint hoặc nhóm endpoint:
   - Option 1 (Recommended): design đề xuất
   - Option 2: variant phổ biến khác (ví dụ paginate vs full list)
   - Option 3: skip endpoint này
6. Append section "API Design" vào spec.md theo design đã chốt.
7. Báo cáo: số endpoint propose, số đã chốt.

## Cấm

- CẤM propose endpoint không có use case trong spec.
- CẤM design response chứa field không có trong DB/model (kiểm tra trước hoặc note "field này cần thêm vào model").
- CẤM bỏ status codes lỗi (401 cho auth, 422 cho validation, 404 cho not-found).
- CẤM bịa response shape — phải dựa trên `ResponseAPI::success/error` format.
- CẤM tự code endpoint — chỉ thiết kế. Code là việc của `implementer`.
- CẤM bỏ qua quyền access (subscription plan, owner-only) nếu spec có nghiệp vụ liên quan.
