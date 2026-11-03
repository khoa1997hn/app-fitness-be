# Spec: Yêu cầu Private, Trị liệu và suất người thêm

## Bối cảnh

Gói Private và trị liệu không đặt theo thời khóa biểu: học viên gửi yêu cầu, admin duyệt. Screen doc: `fitness-web/docs/screens/student/my-courses.md`, `admin/booking-management.md`.

## Phạm vi

### In-scope
- Hội viên: `POST /private-requests`, `POST /therapy-requests`, `PATCH .../{id}/cancel`, `GET /users/me/private-requests`, `GET /users/me/therapy-requests`, `GET /users/me/extra-people`
- Mua suất: `POST /orders` với `type=extra_people`, `quantity`
- `bookings.manage`: danh sách, chi tiết yêu cầu; `GET /admin/bookings/members?group=private`, `GET /admin/bookings/therapy-members`
- `bookings.private.edit_form`: sửa yêu cầu đang chờ và lịch đã duyệt chưa bắt đầu
- `bookings.private.approve`: duyệt, từ chối; hoàn thành buổi Private
- `bookings.cancel_class`: hủy lịch đã duyệt

### Out-of-scope
- Redlight: phase 9
- Thông báo cho học viên: phase 7
- Chia sẻ credit: chưa có nghiệp vụ

## Nghiệp vụ

### Loại gói
- `private_pack`: mua theo gói hoặc buổi lẻ, **được** thêm người
- `private_group`: mua theo buổi lẻ, **không được** thêm người

### Suất người tham gia thêm
- Thuộc **tài khoản**, mua riêng, dùng chung cho mọi gói `private_pack`
- Không có bảng số dư: `balance` = đã mua (đơn `extra_people` thành công) − đã dùng (yêu cầu `completed`). `available` = `balance` − đang giữ (yêu cầu `pending`, `confirmed`)
- Mỗi yêu cầu không vượt `available` và `extra_people_session_max` của gói
- Đơn mua vào thẳng `pending`, không cần ký điều khoản

### Private
- Gửi yêu cầu: giờ ở tương lai, trong hạn gói, `people_count ≤ base_people_max`, số yêu cầu đang mở chưa vượt số buổi còn lại. Chưa trừ gì
- Admin duyệt → `confirmed`
- Admin sửa được form khi `pending`, hoặc `confirmed` mà buổi chưa bắt đầu; điều kiện giống lúc gửi. Sửa lịch `confirmed` thì gửi thông báo cho học viên
- Admin xác nhận hoàn thành, chỉ sau giờ bắt đầu → `completed`, trừ 1 buổi và suất người thêm, kể cả khi vắng
- Học viên hủy: `pending` lúc nào cũng được; `confirmed` từ 8 tiếng trở lên trước giờ tập. Trong vòng 8 tiếng bị chặn
- Admin hủy lịch đã duyệt: không trừ buổi

### Trị liệu
- Gửi yêu cầu: số yêu cầu chờ duyệt chưa vượt số buổi còn lại
- Admin duyệt → trừ 1 buổi ngay
- Hủy hợp lệ sau khi duyệt → hoàn 1 buổi. Trong vòng 8 tiếng học viên không hủy được, admin vẫn hủy được và hoàn buổi

## Acceptance criteria

- [x] Số dư dùng chung giữa các gói `private_pack`, giữ chỗ khi gửi yêu cầu, trả lại khi bị từ chối
- [x] `private_group` thêm người bị từ chối
- [x] Admin sửa được lịch đã duyệt trước giờ tập, học viên nhận thông báo; buổi đã bắt đầu hoặc đã kết thúc thì không sửa được
- [x] Private chỉ trừ khi hoàn thành, không hoàn thành được trước giờ tập, không trừ hai lần
- [x] Trị liệu trừ khi duyệt, hoàn khi hủy hợp lệ
- [x] Phân quyền: lễ tân chỉ xem, HLV không vào, học viên khác không thao tác được

Test: `tests/Feature/Gym/BookingRequestFlowTest.php`.

## Quyết định

- 2026-11-02 — Slot thêm người: thuộc tài khoản, mua riêng, dùng chung cho các gói được phép thêm người
- 2026-11-02 — Private Group mua theo buổi lẻ, không được thêm người; Private pack mua theo gói hoặc buổi lẻ, được thêm người

## Liên quan

- [bookings](../bookings/spec.md), [orders](../orders/spec.md)
