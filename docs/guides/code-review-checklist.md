# Code review checklist

Dùng cho các reviewer agent. Mỗi reviewer chỉ check phần của mình.

## Reviewer-rules

Đối chiếu code với `docs/rules/*`:

- [ ] Logic CRUD đặt trong Controller (không tạo Service bừa) — `01-architecture.md`
- [ ] Không có Repository class — `01-architecture.md`
- [ ] Eloquent gọi qua `Model::query()` — `02-code-quality.md`
- [ ] Catch dùng `\Throwable` — `02-code-quality.md`
- [ ] Throw qua domain exception, không `\Exception` ad-hoc — `02-code-quality.md`
- [ ] Enum trong signature là `string` — `02-code-quality.md`
- [ ] Models đặt trong `app/Share/Models/` — `03-project-structure.md`
- [ ] API Web map field, không return full model — `04-api-response.md`
- [ ] Admin view dùng tiếng Việt — `05-admin-blade.md`
- [ ] Endpoint Web V1 có Swagger attribute (style `#[OA\...]`) — `08-swagger.md`
- [ ] Code logic KHÔNG gọi `env(...)` trực tiếp, đi qua `config(...)` — `09-magic-and-env.md`
- [ ] Env mới có prefix provider/module rõ ràng — `09-magic-and-env.md`
- [ ] Env mới đồng bộ ở CẢ `.env` lẫn `.env.example` — `09-magic-and-env.md`
- [ ] Env mới có mapping vào file config phù hợp (tạo file mới nếu cần) — `09-magic-and-env.md`
- [ ] Migration: field tối thiểu, không phòng xa (`created_by`/`deleted_at`/`status`/`order`/`meta`/`slug`...) — `10-database-design.md`
- [ ] Migration: nullable đúng nghiệp vụ, type nhỏ nhất đủ dùng, tên column theo convention — `10-database-design.md`
- [ ] Migration: chỉ index cột có query thực; FK kèm `onDelete()` quyết định rõ — `10-database-design.md`

## Reviewer-smell

- [ ] Có method/class > ~50 dòng nên tách?
- [ ] Có biến/method không dùng (dead code)?
- [ ] Có comment giải thích "WHAT" thay vì "WHY"? → xóa.
- [ ] Có abstraction chỉ dùng 1 chỗ? → inline.
- [ ] Có if lồng > 3 cấp? → early return.
- [ ] Có magic text/số (status, plan, type, limit, retry…) → enum/config — `09-magic-and-env.md`.

## Reviewer-security

- [ ] Input từ request có validate đầy đủ?
- [ ] Có dùng `whereRaw`/`DB::raw` với input chưa escape?
- [ ] Có mass assignment qua `$request->all()` mà model không có `$fillable`?
- [ ] Endpoint cần auth có middleware?
- [ ] Authorization (chỉ owner truy cập resource của mình) đúng chưa?
- [ ] Có log/return field nhạy cảm (password, token, secret)?
- [ ] File upload có check extension/size/mime?
- [ ] XSS trong Blade — biến chưa escape (`{!! !!}`) có cần thiết?

## Reviewer-duplicate

- [ ] Có logic trùng ở > 1 chỗ → tách thành method/service?
- [ ] Có query Eloquent giống hệt ở > 1 controller → tách thành scope?
- [ ] Có Blade partial trùng → tách `@include`?

**Quan trọng**: reviewer-duplicate khi phát hiện duplicate phải FIX luôn, không chỉ report.

## Cleaner (sau 4 reviewer)

Xem chi tiết quy trình trong `docs/agents/cleaner.md`. Tóm tắt 5 nhóm rác:

- [ ] File rác (`*.bak`, `*Copy.php`, asset không reference)
- [ ] Code rác (`use` không dùng, biến gán không đọc, method 0 reference, comment-out code)
- [ ] Env rác (`.env` ↔ `.env.example` đồng bộ key; env có trong `.env` mà không gọi qua `env()` ở config thì đề xuất xóa)
- [ ] Route / View rác (route trỏ method không tồn tại, Blade 0 reference)
- [ ] Translation key rác (key trong `lang/` không gọi qua `__()`/`trans()`)
