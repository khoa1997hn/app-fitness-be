# Spec: Sự kiện, vé và check-in

## Bối cảnh

Hội viên đăng ký sự kiện Bloom, admin quản lý sự kiện và check-in tại cổng. Screen doc: `fitness-web/docs/screens/student/events.md`, `admin/event-management.md`.

## Phạm vi

### In-scope
- Công khai: `GET /events`, `GET /events/{id}`
- `student.portal`: `POST /events/{id}/register` (vé miễn phí), `POST /orders` với `type=event`, `GET /users/me/event-registrations`
- `events.manage`: `GET|POST /admin/events`, `GET|PATCH /admin/events/{id}`, `PATCH .../status`, `GET .../registrations`, `PATCH /admin/registrations/{id}/checkin`
- Command `gym:release-event-holds` chạy mỗi phút

### Out-of-scope
- Ảnh sự kiện
- Quét QR check-in: admin bấm nút trên danh sách
- Học viên tự hủy vé
- Hoàn tiền tự động

## Nghiệp vụ

### Số vé
- Không lưu riêng, tính từ `event_registrations`. `tickets_left` = tổng − đã bán − đang giữ
- Khóa dòng sự kiện khi đăng ký để không bán vượt
- Mỗi hội viên một lượt đăng ký còn hiệu lực cho một sự kiện, tối đa 20 vé một lượt
- Sửa `total_tickets` không được nhỏ hơn số đã bán và đang giữ

### Vé miễn phí
Đăng ký xong có mã vé ngay, `status=paid`, không có đơn hàng.

### Vé có phí
- Tạo đơn: **giữ vé ngay, trong 15 phút** (`GYM_EVENT_HOLD_MINUTES`), kể cả khi chưa ký điều khoản
- Ký, thanh toán trong thời hạn: `status=paid`, gửi thông báo kèm mã vé
- Quá hạn: vé thôi tính ngay, không ký và không thanh toán được nữa. Command chuyển vé sang `cancelled`, đơn sang `cancelled` và báo cho hội viên
- `ticket_code`: `EVT-{event_id}-{registration_id}`

### Trạng thái sự kiện
- `open`: bán vé. Danh sách công khai chỉ gồm sự kiện `open` chưa diễn ra
- `closed`: ngừng nhận đăng ký mới, vé đang giữ vẫn thanh toán được, có thể mở lại
- `cancelled`: trạng thái cuối. Mọi vé bị hủy, đơn chưa thanh toán bị hủy, hội viên nhận thông báo kèm lý do. Vé đã thanh toán có `needs_refund=true`, hoàn tiền thủ công. Không sửa được nữa

### Check-in
Chỉ vé `paid`, mỗi vé một lần. Check-in theo cả lượt đăng ký, không theo từng vé trong lượt.

### Khác
- Học viên tự hủy được đơn vé **chưa thanh toán** (`POST orders/{code}/cancel`): vé và đơn sang `cancelled`, vé trả lại ngay, không gửi thông báo. Vé đã thanh toán chưa tự hủy được, vì hoàn tiền còn thủ công
- Đơn vé chưa thanh toán trả `hold_expires_at` để FE đếm ngược
- Nhắc lịch trước sự kiện qua `gym:send-reminders`
- Tài khoản đã có vé sự kiện không xóa được, chỉ khóa

## Acceptance criteria

- [x] Danh sách công khai không cần đăng nhập, chỉ gồm sự kiện mở chưa diễn ra
- [x] Vé đang giữ chặn bán vượt; quá hạn thì người khác mua được
- [x] Giữ chỗ quá hạn không ký, không thanh toán được; nhả vé chạy lại an toàn
- [x] Vé miễn phí và có phí đi đúng luồng; không đăng ký trùng
- [x] Không giảm số vé xuống dưới số đã bán và đang giữ
- [x] Check-in chỉ vé đã thanh toán, một lần
- [x] Hủy sự kiện: hủy vé, thông báo, đánh dấu cần hoàn tiền; không mở lại, không sửa được
- [x] Đóng sự kiện: vé đang giữ vẫn thanh toán được; mở lại bán tiếp

Test: `tests/Feature/Gym/EventFlowTest.php`.

## Quyết định

- 2026-11-02 — Giữ vé ngay khi tạo đơn, 30 phút, quá hạn tự nhả
- 2026-11-02 — Học viên chưa tự hủy được vé trong giai đoạn đầu

## Liên quan

- [orders](../orders/spec.md), [users-account](../users-account/spec.md)
