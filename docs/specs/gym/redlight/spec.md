# Spec: Redlight theo lượt

## Bối cảnh

Hội viên mua trước một số lượt dùng thảm Redlight (giống mua suất người thêm), đặt lớp nào dùng Redlight thì trừ 1 lượt, lịch bị hủy thì hoàn lượt. Thay cho cách trả tiền từng buổi trước đây vì hoàn tiền khó. Không quản lý tồn kho. Screen doc: `fitness-web/docs/screens/student/my-courses.md`.

## Phạm vi

### In-scope
- `student.portal`: `POST /orders` với `type=redlight`, `quantity`; `GET /users/me/redlight` xem số dư
- `POST /bookings` nhận `use_redlight`; `POST|DELETE /bookings/{id}/redlight` thêm/bỏ Redlight cho lịch đã đặt
- `GET /schedules`, `GET /users/me/bookings` trả trường Redlight
- Nhân viên thấy ai dùng Redlight ở điểm danh, chi tiết lịch dạy, chi tiết booking
- Nhân viên tạo đơn hộ bán lượt Redlight như sản phẩm khác

### Out-of-scope
- Tồn kho thảm, giới hạn số thảm mỗi lớp
- Hạn sử dụng lượt
- Lịch Private, trị liệu

## Nghiệp vụ

- **Giá:** `GYM_REDLIGHT_PRICE`, mặc định 30.000đ **mỗi lượt**; đơn = số lượt × giá, chốt lúc tạo
- **Mua:** nhập số lượt tùy ý, không ký điều khoản, vào thẳng `pending`
- **Số dư:** không lưu, tính = tổng lượt của đơn đã thanh toán − số lịch `confirmed` có `has_redlight`
- **Áp dụng:** mọi lớp nhóm. Không áp dụng Private và trị liệu
- **Dùng:** tích khi đặt lịch, hoặc thêm cho lịch đã đặt khi lớp chưa bắt đầu. Hết lượt thì bị chặn (đặt lịch kèm Redlight thì không đặt)
- **Bỏ:** bỏ Redlight được trước giờ học tối thiểu 8 tiếng (cùng mốc khóa hủy lịch), hoàn lượt
- **Hủy lịch:** học viên hủy, nhân viên hủy, hủy lớp → tự hoàn lượt, thông báo hủy nói rõ đã hoàn
- **Không đến:** mất lượt, như mất buổi

## Acceptance criteria

- [x] Mua N lượt, không ký điều khoản, số dư tăng khi thanh toán
- [x] Đặt lịch kèm Redlight trừ 1 lượt, mọi lớp nhóm; hết lượt thì không đặt
- [x] Thêm/bỏ Redlight cho lịch đã đặt; bỏ bị chặn trong vòng 8 tiếng; lớp đã bắt đầu không thêm được
- [x] Lịch của người khác trả 404
- [x] Hủy lịch, hủy lớp hoàn lượt
- [x] Đã học hoặc vắng vẫn tính đã dùng
- [x] Nhân viên thấy học viên nào dùng Redlight

Test: `tests/Feature/Gym/RedlightFlowTest.php`.

## Quyết định

- 2026-11-02 — Tính theo buổi, chỉ lớp Barre, Pilates
- 2026-11-04 — Hoàn tiền thủ công, chỉ admin xác nhận
- 2026-11-06 — Đổi sang mua trước theo lượt, áp dụng mọi lớp nhóm, lịch hủy hoàn lượt. Bỏ hoàn tiền thủ công, quyền `bookings.redlight_refund`, command hủy đơn chờ. Chưa có dữ liệu thật nên bỏ hẳn cột cũ, không chuyển đổi

## Liên quan

- [bookings](../bookings/spec.md), [orders](../orders/spec.md)
