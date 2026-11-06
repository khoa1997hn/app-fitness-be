# Spec: Quên mật khẩu qua liên kết xác nhận

## Luồng hiện tại (2026-11-06)

1. `POST /api/v1/auth/password/reset` nhận `{ email }` (required, email, max 255), public, không cần JWT.
2. Nếu tài khoản tồn tại và chưa bị xóa, gửi email song ngữ Việt/Anh chứa link `${FRONTEND_URL}/reset-password?token=...&email=...`. Không đổi mật khẩu khi gửi email và không gửi mật khẩu trong email.
3. Luôn trả 200 với thông báo chung cho email hợp lệ, kể cả email không tồn tại, đã xóa hoặc đang trong khoảng chờ gửi lại.
4. Dùng Laravel password broker và bảng `password_reset_tokens` hiện có: token lưu dạng hash, hết hạn theo `auth.passwords.users.expire` (60 phút), giới hạn gửi lại theo `throttle` (60 giây). Link mới thay thế link trước.
5. Trang `/reset-password` công khai, cho nhập mật khẩu mới và xác nhận; có link yêu cầu gửi lại nếu link thiếu, hết hạn hoặc không hợp lệ.
6. `POST /api/v1/auth/password/confirm` nhận `{ email, token, password, password_confirmation }`. Mật khẩu `required|string|min:8|max:50|confirmed`.
7. Token phải hợp lệ cho đúng email, chưa hết hạn/chưa sử dụng. Thành công: cập nhật password qua hashed cast, xóa token, trả 200; người dùng quay lại đăng nhập. Không xác thực email tài khoản hoặc tự đăng nhập trong bước này.
8. Input sai hoặc token không hợp lệ/đã sử dụng/hết hạn → 422. Không đổi mật khẩu và không tiêu thụ token khi validation thất bại.
9. Admin gửi yêu cầu reset dùng chung luồng gửi link; giữ nguyên phân quyền quản lý user.

## Ngoài phạm vi

- Thu hồi JWT đã cấp (giữ hành vi hiện tại).

## Acceptance criteria

- [x] Gửi email không làm thay đổi mật khẩu hiện tại.
- [x] Email chứa nút và URL dự phòng, song ngữ Việt/Anh.
- [x] Người dùng tự nhập và xác nhận mật khẩu mới.
- [x] Token gắn đúng email, hết hạn, dùng một lần; link mới vô hiệu link trước.
- [x] Email không tồn tại hoặc đã xóa không nhận mail, API vẫn trả thông báo chung.
- [x] Admin reset gửi link và không đổi password ngay.
- [x] API và frontend đồng bộ luồng, có OpenAPI.

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

## Quyết định 2026-11-06

- Theo yêu cầu người dùng: thay việc gửi mật khẩu ngẫu nhiên bằng link xác nhận để nhập mật khẩu mới. Thay thế luồng và ngoài phạm vi cũ ở trên.
- Giữ endpoint gửi yêu cầu `/auth/password/reset`; bổ sung `/auth/password/confirm` để lưu mật khẩu. Dùng cấu hình expiry/throttle hiện có của Laravel.
- Email song ngữ theo quy ước email hiện tại của dự án.
