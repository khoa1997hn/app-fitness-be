# Spec: Điểm danh, lịch dạy và yêu cầu hủy của HLV

## Bối cảnh

Nhân viên điểm danh lớp gói thường, xem lịch dạy thật, HLV xin hủy lịch và admin duyệt. Screen doc: `fitness-web/docs/screens/admin/attendance-management.md`, `admin/schedule-management.md`.

## Phạm vi

### In-scope
- `attendance.view`: `GET /admin/attendance/sessions?date=`
- `attendance.mark`: `POST /admin/attendance/records`, `POST /admin/attendance/sessions/{schedule}/mark-all-present`
- `schedules.teaching.view`: `GET /admin/schedules`, `GET /admin/schedules/{type}/{id}`, với `type` là `class` hoặc `private`
- `schedules.teaching.cancel`: `PATCH /admin/schedules/{type}/{id}/cancel`
- `schedules.cancel_request.create`: `POST /instructor/schedules/{type}/{id}/cancel-request`
- `schedules.cancel_request.review`: `GET /admin/schedule-cancel-requests`, `PATCH .../{id}/approve`, `PATCH .../{id}/reject`

### Out-of-scope
- Thông báo cho học viên khi lịch bị hủy: phase 7
- Điểm danh ở chi tiết học viên: phase 7
- Lịch trị liệu: `therapy_requests` chưa có HLV

## Nghiệp vụ

### Điểm danh
- Chỉ áp dụng lớp gói thường. **Buổi Private không điểm danh** vì là lịch đặt riêng, ít người, đã có bước xác nhận hoàn thành
- Chỉ ghi hoặc sửa trong ngày diễn ra lớp, theo `Asia/Ho_Chi_Minh`
- Mỗi booking một bản ghi, ghi lại thì cập nhật
- Không trừ buổi: `gym:deduct-credits` trừ mọi booking đã qua giờ, kể cả khi vắng
- `absent_recent`: số buổi vắng trong 5 lần điểm danh gần nhất, bỏ qua booking đã hủy

### Lịch dạy
- Gồm mọi lớp trên thời khóa biểu, kể cả chưa có người đặt, và buổi Private đã được admin duyệt
- Trạng thái lớp: `cancelled` nếu lớp hủy, `completed` nếu đã qua giờ kết thúc, còn lại `confirmed`
- Trạng thái Private lấy theo yêu cầu. Yêu cầu chưa từng được duyệt không hiện
- HLV chỉ thấy lịch có `trainer_id` của mình, kiểm tra bằng ID ở BE
- Lễ tân xem được lịch của mọi HLV, không hủy và không gửi yêu cầu hủy
- Admin hủy lịch `confirmed`: học viên không bị trừ buổi, suất người thêm được trả lại

### Yêu cầu hủy của HLV
- HLV gửi kèm lý do, **được gửi cả trong vòng 8 tiếng trước giờ dạy**, miễn là buổi chưa bắt đầu
- Mỗi lịch chỉ có một yêu cầu đang chờ
- Duyệt: lịch bị hủy, học viên không bị trừ buổi. Nếu lịch đã bị hủy nơi khác thì chỉ đóng yêu cầu
- Từ chối: lịch giữ nguyên
- Không duyệt được khi buổi đã diễn ra, hoặc lớp đã đổi sang HLV khác. Khi đó admin từ chối yêu cầu

## Acceptance criteria

- [x] Điểm danh không trừ buổi; học viên vắng vẫn bị trừ sau giờ học
- [x] Không điểm danh được ngoài ngày học hoặc với booking đã hủy
- [x] HLV không xem, không thao tác được trên lịch của HLV khác
- [x] Lễ tân điểm danh được, xem được lịch dạy nhưng không hủy được
- [x] HLV gửi yêu cầu trong vòng 8 tiếng được; duyệt xong học viên không mất buổi, kể cả sau khi job trừ buổi chạy
- [x] Từ chối thì lịch giữ nguyên; không xử lý một yêu cầu hai lần

Test: `tests/Feature/Gym/AttendanceTeachingFlowTest.php`.

## Quyết định

- 2026-11-02 — Buổi Private không cần điểm danh
- 2026-11-02 — HLV được gửi yêu cầu hủy trong vòng 8 tiếng; admin duyệt thì học viên không bị trừ buổi

## Liên quan

- [bookings](../bookings/spec.md), [private-therapy](../private-therapy/spec.md)
