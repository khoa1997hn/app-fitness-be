# Spec: Quản lý user, tài khoản và thông báo

## Bối cảnh

Nhân viên quản lý tài khoản thật thay cho dữ liệu mock, hội viên tự sửa hồ sơ và nhận thông báo. Screen doc: `fitness-web/docs/screens/admin/user-management.md`, `student/user-dashboard.md`.

## Phạm vi

### In-scope
- `users.manage`: `GET|POST /admin/users`, `GET|PATCH /admin/users/{id}`, `PATCH .../status`, `POST .../reset-password`
- `users.change_role`: `PATCH /admin/users/{id}/role`
- `users.extend_package`: `PATCH /admin/users/{id}/packages/{pid}/extend`, `.../therapies/{tid}/extend`
- `records.delete`: `DELETE /admin/users/{id}`
- `student.portal`: `PATCH /users/me`, `GET /users/me/notifications`, `PATCH /notifications/{id}/read`
- Mọi tài khoản đăng nhập: `PATCH /users/me/password`
- Command `gym:send-reminders` chạy mỗi giờ

### Out-of-scope
- Bắt đổi mật khẩu tạm ở lần đăng nhập đầu
- Thông báo qua email hoặc push
- Thông báo sự kiện: phase 8

## Nghiệp vụ

### Tài khoản
- **Giữ tự đăng ký công khai** vì app cũng dùng API này
- **Bỏ `username`**, chỉ đăng nhập bằng email
- `users` thêm `status` (`active`, `locked`, mặc định `active`) và `gender`. Bản ghi cũ giữ nguyên, app không phải đổi
- Không sửa được email qua các API này

### Khóa tài khoản
- `locked` không đăng nhập được (403) và token còn hạn bị từ chối (401)
- Áp dụng cho mọi API, **gồm cả app**, vì dùng chung `users`. Người chưa bị khóa không bị ảnh hưởng

### Phân quyền thao tác
- Lễ tân, HLV: sửa, khóa, gửi liên kết đặt lại mật khẩu **chỉ với hội viên**; chỉ tạo được hội viên; xem được mọi user
- Chỉ admin đổi role, tạo nhân viên, gia hạn, xóa
- Không tự khóa, tự đổi role hoặc tự xóa mình qua API admin

### Xóa tài khoản
- Đã có gói, đơn hàng phòng tập hoặc lịch dạy thì không xóa được (422), chỉ khóa. Áp dụng cả `DELETE /auth/me`
- Chưa có thì xóa mềm như trước

### Gia hạn
- Gói đã hết hạn và còn buổi. Cộng số ngày vào `end_date`, không thêm buổi

### Thông báo
- Tạo khi: thanh toán thành công; duyệt, từ chối, hủy yêu cầu Private và trị liệu; hủy booking, hủy lớp, kể cả khi duyệt yêu cầu hủy của HLV; gia hạn gói
- Nhắc lịch trước `GYM_REMINDER_HOURS` (mặc định 24), báo gói sắp hết hạn trước `GYM_PACKAGE_EXPIRY_NOTICE_DAYS` (mặc định 7). Không gửi lại nhờ `dedupe_key`
- Hội viên chỉ thấy và đánh dấu được thông báo của mình

## Acceptance criteria

- [x] Khóa có hiệu lực ngay với cả token cũ; mở khóa đăng nhập lại được
- [x] Đăng ký mới có `status=active`
- [x] Lễ tân, HLV không sửa, khóa, reset được tài khoản admin; không đổi được role; không tạo được nhân viên
- [x] Không sửa được email, role qua API hồ sơ
- [x] Không xóa được tài khoản đã có dữ liệu, cả ở admin và tự xóa
- [x] Chỉ gia hạn gói hết hạn còn buổi, gói phải thuộc đúng user
- [x] Các sự kiện tạo đúng thông báo; nhắc lịch chạy lại không gửi trùng

Test: `tests/Feature/Gym/UserAccountFlowTest.php`.

## Quyết định

- 2026-11-02 — Giữ tự đăng ký; chặn xóa khi còn gói hoặc đơn phòng tập; bỏ `username`

## Liên quan

- [roles](../roles/spec.md), [orders](../orders/spec.md), [private-therapy](../private-therapy/spec.md), [attendance-teaching](../attendance-teaching/spec.md)
