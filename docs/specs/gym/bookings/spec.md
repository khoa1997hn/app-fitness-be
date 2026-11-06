# Spec: Thời khóa biểu và đặt lịch gói thường

## Bối cảnh

Hội viên dùng gói đã mua để đặt lớp trên thời khóa biểu do admin xếp. Screen doc: `fitness-web/docs/screens/student/my-courses.md`, `admin/schedule-management.md`, `admin/booking-management.md`.

## Phạm vi

### In-scope
- Catalog 20 lớp, 6 nhóm (`FitnessClass`, enum `FitnessClassCategory`)
- Hội viên (`student.portal`): `GET /schedules[/{id}]`, `GET /users/me/bookings`, `POST /bookings`, `PATCH /bookings/{id}/cancel`
- Thời khóa biểu (`schedules.timetable.manage`): `GET|POST /admin/timetable`, `GET|PATCH /admin/timetable/{id}`, `PATCH /admin/timetable/{id}/cancel`, `GET /admin/timetable/options`
- Booking (`bookings.manage`): `GET /admin/bookings/members[/{id}]`; hủy thay học viên `PATCH /admin/bookings/{id}/cancel` (`bookings.cancel_class`)

### Out-of-scope
- Private, trị liệu: phase 5
- Điểm danh, lịch dạy HLV, yêu cầu hủy: phase 6
- Xuất file

## Nghiệp vụ

**Đặt lịch** trừ 1 buổi ngay. Điều kiện: gói của chính user, không phải Private, lớp đang mở và chưa bắt đầu, gói còn hạn tới ngày học, đúng khung off-peak nếu là gói off-peak, chưa đặt buổi này, còn chỗ, còn buổi (`remaining_credits` > 0). Kiểm tra trong transaction có khóa dòng lớp và gói.

**Hủy:** còn từ 8 tiếng trở lên trước giờ học thì hủy được và được hoàn buổi. Trong vòng 8 tiếng bị chặn.

**Trừ buổi:** trừ lúc đặt, ghi `credit_deducted_at`. Không đến vẫn mất buổi, sau giờ học không trừ thêm. Hủy hợp lệ thì hoàn buổi và xóa `credit_deducted_at`. Gói `unlimited` không đổi số buổi.

**Xếp lịch:** lịch mới phải bắt đầu ở tương lai và ở trạng thái `active`. Khi sửa, đổi giờ bắt đầu thì giờ mới phải ở tương lai.

**Nhân viên hủy** (một booking hoặc cả lớp) hoàn buổi cho học viên. Lớp đã qua giờ thì không hủy được booking, không hoàn buổi. Không giảm được `max_slot` xuống dưới số đã đặt.

**Off-peak:** gói `is_off_peak` chỉ đặt được lớp bắt đầu từ 08:00 trở về trước, hoặc từ 23:00 trở đi. Tính theo giờ bắt đầu, cấu hình `GYM_OFF_PEAK_MORNING_END`, `GYM_OFF_PEAK_EVENING_START`.

## Acceptance criteria

- [x] Đặt lịch trừ 1 buổi ngay; hủy hợp lệ hoàn đúng 1 buổi; sau giờ học không trừ thêm, kể cả vắng
- [x] Trước đúng 8 tiếng hủy được, 7 tiếng 59 phút bị chặn
- [x] Không đặt quá số buổi còn lại, không đặt lớp đầy, không đặt trùng
- [x] Biên off-peak 08:00, 08:01, 22:59, 23:00
- [x] Admin hủy hoàn buổi kể cả trong vòng 8 tiếng; hủy lớp hủy luôn booking và hoàn buổi
- [x] Phân quyền theo role, người khác không hủy được booking

Test: `tests/Feature/Gym/BookingFlowTest.php`.

## Quyết định

- 2026-11-02 — Hủy trong vòng 8 tiếng → chặn, vẫn tính buổi, không đến vẫn mất buổi
- 2026-11-02 — Đặt lịch rồi không tới → vẫn trừ buổi
- 2026-11-02 — Off-peak → gói riêng, khung trước 8h và sau 23h
- 2026-11-03 — Trừ buổi ngay khi đặt để danh sách gói phản ánh đúng số buổi; thay quy tắc trừ sau giờ học

## Vận hành

Trừ buổi, trừ lượt Redlight không phụ thuộc scheduler.

Khi triển khai, migration `deduct_credits_of_held_bookings` trừ buổi cho các booking đang giữ theo quy tắc cũ.

## Liên quan

- [orders](../orders/spec.md), [packages](../packages/spec.md)
