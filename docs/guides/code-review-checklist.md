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
- [ ] Endpoint Web V1 có Swagger annotations — `08-swagger.md`

## Reviewer-smell

- [ ] Có method/class > ~50 dòng nên tách?
- [ ] Có biến/method không dùng (dead code)?
- [ ] Có comment giải thích "WHAT" thay vì "WHY"? → xóa.
- [ ] Có abstraction chỉ dùng 1 chỗ? → inline.
- [ ] Có if lồng > 3 cấp? → early return.
- [ ] Có magic number/string lặp lại? → hằng số hoặc enum.

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
