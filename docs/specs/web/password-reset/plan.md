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
