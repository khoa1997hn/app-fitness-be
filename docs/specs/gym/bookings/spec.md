# Spec: Thời khóa biểu và đặt lịch gói thường

## Bối cảnh

Hội viên dùng gói đã mua để đặt lớp trên thời khóa biểu do admin xếp. Screen doc: `fitness-web/docs/screens/student/my-courses.md`, `admin/schedule-management.md`, `admin/booking-management.md`.

## Phạm vi

### In-scope
- Catalog 20 lớp, 6 nhóm (`FitnessClass`, enum `FitnessClassCategory`)
- Hội viên (`student.portal`): `GET /schedules[/{id}]`, `GET /users/me/bookings`, `POST /bookings`, `PATCH /bookings/{id}/cancel`
- Thời khóa biểu (`schedules.timetable.manage`): `GET|POST /admin/timetable`, `GET|PATCH /admin/timetable/{id}`, `PATCH /admin/timetable/{id}/cancel`, `GET /admin/timetable/options`
- Booking (`bookings.manage`): `GET /admin/bookings/members[/{id}]`; hủy thay học viên `PATCH /admin/bookings/{id}/cancel` (`bookings.cancel_class`)
- Command `gym:deduct-credits`, chạy 5 phút một lần qua scheduler

### Out-of-scope
- Private, trị liệu: phase 5
- Điểm danh, lịch dạy HLV, yêu cầu hủy: phase 6
- Xuất file

## Nghiệp vụ

**Đặt lịch** chỉ giữ buổi, không trừ. Điều kiện: gói của chính user, không phải Private, lớp đang mở và chưa bắt đầu, gói còn hạn tới ngày học, đúng khung off-peak nếu là gói off-peak, chưa đặt buổi này, còn chỗ, còn buổi. "Còn buổi" = `remaining_credits` trừ số buổi đã đặt chưa bị trừ. Kiểm tra trong transaction có khóa dòng lớp và gói.

**Hủy:** còn từ 8 tiếng trở lên trước giờ học thì hủy được, không mất buổi. Trong vòng 8 tiếng bị chặn.

**Trừ buổi:** `gym:deduct-credits` trừ 1 buổi cho mọi booking còn hiệu lực đã qua giờ kết thúc, **kể cả học viên không đến**. Chạy lại không trừ lần hai nhờ `credit_deducted_at`. Gói `unlimited` không đổi số buổi.

**Nhân viên hủy** (một booking hoặc cả lớp) không trừ buổi, trừ khi buổi đã bị trừ. Không giảm được `max_slot` xuống dưới số đã đặt.

**Off-peak:** gói `is_off_peak` chỉ đặt được lớp bắt đầu từ 08:00 trở về trước, hoặc từ 23:00 trở đi. Tính theo giờ bắt đầu, cấu hình `GYM_OFF_PEAK_MORNING_END`, `GYM_OFF_PEAK_EVENING_START`.

## Acceptance criteria

- [x] Đặt lịch không trừ buổi; sau giờ học trừ đúng 1 buổi, kể cả vắng
- [x] Trước đúng 8 tiếng hủy được, 7 tiếng 59 phút bị chặn
- [x] Không đặt quá số buổi còn lại, không đặt lớp đầy, không đặt trùng
- [x] Biên off-peak 08:00, 08:01, 22:59, 23:00
- [x] Admin hủy không mất buổi kể cả trong vòng 8 tiếng; hủy lớp hủy luôn booking
- [x] Phân quyền theo role, người khác không hủy được booking

Test: `tests/Feature/Gym/BookingFlowTest.php`.

## Quyết định

- 2026-11-02 — Hủy trong vòng 8 tiếng → chặn, vẫn tính buổi, không đến vẫn mất buổi
- 2026-11-02 — Đặt lịch rồi không tới → vẫn trừ buổi
- 2026-11-02 — Off-peak → gói riêng, khung trước 8h và sau 23h

## Vận hành

Cần chạy scheduler thì buổi mới được trừ: cron `php artisan schedule:run` mỗi phút trên server, `php artisan schedule:work` khi dev.

## Liên quan

- [orders](../orders/spec.md), [packages](../packages/spec.md)
