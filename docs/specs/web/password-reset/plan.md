# Plan: API password reset (email)

## Phụ thuộc
- Migration: không
- Mail: Laravel Mail + Mailable

## Các pha

### Pha 1 — Service + Mailable
- `ForgotPasswordService` — generate password, update user, send mail
- `ForgotPasswordMail` + blade view (EN)

### Pha 2 — Controller + route + i18n + OpenAPI
- `PasswordResetController@reset`
- `routes/api.php`
- `messages.password_reset_email_sent`

## Verify
- [ ] POST với email user thật → mail log/SMTP + login OK
- [ ] POST email giả → 200, không crash

## Update 2026-06-06

### Pha — Template mail Markdown
- Đổi `ForgotPasswordMail` → `Content::markdown('mail.forgot-password')`
- View dùng `<x-mail::message>` thay HTML thuần

### Verify
- [ ] Email render đúng layout Markdown mặc định của Laravel


## Update 2026-11-06

- Dùng password broker có sẵn để gửi và xác thực token; dùng chung cho auth và admin.
- Bổ sung API confirm và trang public nhập mật khẩu mới; cập nhật nội dung email và frontend.
- Kiểm tra token hết hạn, dùng lại, sai email, tài khoản đã xóa, validation và gửi lại.
