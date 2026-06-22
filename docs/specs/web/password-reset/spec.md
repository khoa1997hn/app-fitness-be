# Spec: API quên mật khẩu (gửi mật khẩu mới qua email)

## Bối cảnh

End-user quên mật khẩu, nhập email → BE sinh mật khẩu ngẫu nhiên đủ mạnh, cập nhật DB, gửi email chứa mật khẩu mới. User đăng nhập bằng mật khẩu mới.

## Phạm vi

### In-scope
- `POST /api/v1/auth/password/reset` — public, không JWT.
- Input: `email` (required, email, max 255).
- BE sinh password random (đúng rule register: `string`, min 8, max 50 ký tự).
- Cập nhật `users.password` (hash qua cast).
- Gửi email **tiếng Anh** chứa mật khẩu plain text.
- Response **luôn 200** + message chung (kể cả email không tồn tại / user soft-deleted) — không lộ email có hay không.

### Out-of-scope
- Link reset token / OTP xác nhận.
- User tự đặt mật khẩu mới qua form.
- Rate limit (phase sau nếu cần).
- Invalidate JWT cũ.

## Nghiệp vụ

1. Client `POST` `{ "email": "..." }`.
2. Validate email format.
3. Tìm user theo email (không gồm soft-deleted).
4. **Không tìm thấy** → vẫn trả 200, không gửi mail.
5. **Tìm thấy** → sinh password (`Str::password`, 12 ký tự, có chữ+số+ký tự đặc biệt), validate rule giống register → update user → gửi mail EN.
6. User login bằng email + password mới.

## Input / Output

### Input
```json
{ "email": "user@example.com" }
```

### Output (200)
```json
{
  "success": true,
  "message": "If an account exists for this email, a new password has been sent.",
  "data": null
}
```
(Message key `messages.password_reset_email_sent` — theo `x-locale` API; **nội dung email** luôn EN.)

### Lỗi
- 422 — validate email.

## Acceptance criteria

- [ ] User tồn tại → nhận email EN có password mới, login được.
- [ ] Email không tồn tại → 200, không gửi mail.
- [ ] User soft-deleted → 200, không gửi mail.
- [ ] Password sinh ra thỏa min 8 max 50.
- [ ] OpenAPI có endpoint.

## Quyết định

- **2026-06-06** — Email không tồn tại → **200 generic**, không tiết lộ.
- **2026-06-06** — Path → `POST /api/v1/auth/password/reset`.
- **2026-06-06** — Email body → **luôn tiếng Anh**.
- **2026-06-06** — Password rule → giống register: `required|string|min:8|max:50`.
- **2026-06-06** — Template email → Laravel Markdown Mail component (`<x-mail::message>`), không HTML thuần.

## Update 2026-06-06

### Thay đổi
- View `mail.forgot-password` dùng **Laravel Markdown Mail** (`Content::markdown` + `<x-mail::message>`).
- Nội dung email giữ nguyên (EN): chào tên, password mới, hướng dẫn đổi mật khẩu, disclaimer.


- `RegistrationController@register`
- `config/mail.php`, `.env` MAIL_*
