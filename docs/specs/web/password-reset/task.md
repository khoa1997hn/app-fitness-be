# Task: password reset API

- [x] spec.md
- [x] plan.md
- [x] ForgotPasswordService
- [x] ForgotPasswordMail + view
- [x] PasswordResetController + route
- [x] messages + OpenAPI + pint + swagger
- [ ] STOP — hỏi user commit/push

## Update 2026-06-06

- [x] Đổi `ForgotPasswordMail` sang `Content::markdown`
- [x] View `mail/forgot-password.blade.php` dùng `<x-mail::message>`
- [ ] STOP — hỏi user commit/push


## Update 2026-11-06

- [x] Đổi service và mail sang link reset, giữ password cũ khi gửi.
- [x] Thêm API confirm và OpenAPI.
- [x] Thêm trang reset, API client, cập nhật UI forgot/admin.
- [x] Thêm kiểm thử bảo mật token, cập nhật kiểm thử email và admin.
- [x] Đồng bộ tài liệu tính năng.

### Validation 2026-11-06

- Sail: `PasswordResetTest`, `MailPresentationTest`, `UserAccountFlowTest`: 20 tests passed, 218 assertions (DB connection overridden to the local Sail MySQL service for this run).
- Pint các file thay đổi và `l5-swagger:generate`: đạt.
- Frontend production build và ESLint các file thay đổi: đạt.
- ESLint toàn frontend: còn 8 errors, 2 warnings ở các file ngoài phạm vi thay đổi.
