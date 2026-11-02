# Spec: Redlight theo buổi

## Bối cảnh

Hội viên trả thêm để dùng thảm Redlight trong một buổi tập. Không quản lý tồn kho. Screen doc: `fitness-web/docs/screens/student/my-courses.md`.

## Phạm vi

### In-scope
- `student.portal`: `POST /orders` với `type=redlight`, `booking_id`
- `GET /schedules`, `GET /users/me/bookings` trả thêm trường Redlight
- Nhân viên thấy ai dùng Redlight ở điểm danh, chi tiết lịch dạy, chi tiết booking

### Out-of-scope
- Tồn kho thảm, giới hạn số thảm mỗi lớp
- Hoàn tiền tự động

## Nghiệp vụ

- **Giá:** `GYM_REDLIGHT_PRICE`, mặc định 30.000đ **mỗi buổi**, chốt vào đơn lúc tạo
- **Áp dụng:** chỉ lớp nhóm Barre, Pilates (`barre`, `core-pilates`). Không áp dụng cho Private và trị liệu
- **Mua:** với lịch đã đặt, còn hiệu lực, lớp chưa bắt đầu. Có thể chọn ngay khi đặt lịch, hoặc mua sau ở tab Tiện ích bổ sung
- **Đơn:** không cần ký điều khoản, vào thẳng `pending`. Đã có đơn chờ của buổi này thì dùng lại
- **Thanh toán:** kiểm tra lại điều kiện; lớp đã bắt đầu hoặc lịch đã hủy thì không thanh toán được
- Không liên quan tới việc trừ buổi

### Khi lịch thay đổi
- Đơn đang chờ bị hủy khi học viên hủy lịch, nhân viên hủy lịch, hủy lớp, hoặc khi lớp kết thúc
- Đã thanh toán mà lịch bị hủy: `needs_redlight_refund=true`, **hoàn tiền thủ công**
- Đã thanh toán mà học viên không đến: không hoàn

## Acceptance criteria

- [x] Mua cho đúng một buổi, không ký điều khoản, không ảnh hưởng buổi khác và số buổi còn lại
- [x] Chỉ Barre, Pilates
- [x] Dùng lại đơn đang chờ; đã trả rồi không mua lại được
- [x] Lịch của người khác trả 404
- [x] Lớp đã bắt đầu không mua và không thanh toán được
- [x] Hủy lịch, lớp kết thúc thì hủy đơn đang chờ
- [x] Hủy lớp sau khi đã trả: đánh dấu cần hoàn tiền, đơn giữ nguyên
- [x] HLV thấy học viên nào dùng Redlight

Test: `tests/Feature/Gym/RedlightFlowTest.php`.

## Quyết định

- 2026-11-02 — Tính theo buổi, học viên chọn khi đặt từng buổi
- 2026-11-02 — Chỉ lớp Barre, Pilates, không áp dụng Private và trị liệu

## Liên quan

- [bookings](../bookings/spec.md), [orders](../orders/spec.md)
