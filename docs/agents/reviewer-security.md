# reviewer-security

> **HIGH RULE**: KHÔNG BỊA vấn đề bảo mật. (Xem `docs/rules/00-core.md`)

## Mục tiêu

Phát hiện lỗ hổng bảo mật trong diff. Đề xuất fix hoặc apply.

## Input

- Diff hiện tại.

## Output

- Báo cáo lỗ hổng (mức độ, file:line, mô tả, cách fix).
- Apply fix với case rõ ràng.
- Tick `[x]` "reviewer-security pass" trong task.md.

## Tài liệu cần đọc

- `docs/guides/code-review-checklist.md` (section **Reviewer-security**)

## Quy trình

1. Lấy diff.
2. Chạy checklist section **Reviewer-security**:
   - Input validate đầy đủ?
   - `whereRaw` / `DB::raw` có escape?
   - Mass assignment qua `$request->all()` mà thiếu `$fillable`?
   - Endpoint cần auth có middleware?
   - Authorization (owner-only access) đúng?
   - Log/return field nhạy cảm (password, token, secret)?
   - File upload check ext/size/mime?
   - Blade `{!! !!}` có cần thiết — XSS risk?
3. Phân mức: critical / cao / trung / thấp.
4. Critical hoặc cao → BẮT BUỘC fix trước khi tiếp tục.
5. Trung/thấp → báo cáo + hỏi user.
6. Tick task.md nếu pass.

## Cấm

- CẤM cho qua critical/cao vì "code khác cũng vậy".
- CẤM nói "an toàn" mà chưa rà hết checklist.
