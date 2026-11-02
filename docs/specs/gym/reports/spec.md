# Spec: Báo cáo và tổng quan

## Bối cảnh

Admin xem doanh thu, học viên, lớp học; mỗi vai trò nhân viên có trang Tổng quan riêng. Screen doc: `fitness-web/docs/screens/admin/reports.md`.

## Phạm vi

### In-scope
- `reports.view` (admin): `GET /admin/reports/overview|revenue|members|classes?from=&to=`
- `dashboard.view` (nhân viên): `GET /admin/dashboard`

### Out-of-scope
- Xuất Excel, PDF: để sau, xem `fitness-web/plan/deferred.md`
- Cache hoặc bảng tổng hợp: chưa cần, xem lại khi dữ liệu lớn

## Nghiệp vụ

- **Khoảng thời gian:** mặc định 30 ngày gần nhất, tối đa 366 ngày, theo `Asia/Ho_Chi_Minh`
- **Doanh thu:** đơn `success`, theo `paid_at`, giá snapshot trên đơn. **Chưa trừ hoàn tiền thủ công**
- **Học viên mới:** người có đơn gói tập hoặc trị liệu đã thanh toán **đầu tiên** rơi vào kỳ. Vé sự kiện, Redlight, suất người thêm không tính; user app chưa mua gì không tính
- **Tỷ lệ mua lại:** phần người mua gói trong kỳ đã từng mua gói trước đó
- **Tỷ lệ hủy lịch:** lượt đã hủy trên tổng lượt đặt gói thường tạo trong kỳ, gồm cả phòng tập hủy. Private và trị liệu không tính
- **Tỷ lệ lấp đầy:** trung bình `đã đặt / số chỗ` của từng buổi, chỉ buổi đã diễn ra và không bị hủy
- **Breakdown:** gói, trị liệu theo sản phẩm; sự kiện theo sự kiện; Redlight, suất người thêm gộp một dòng. `quantity_sold` tính theo số vé, số suất

### Tổng quan theo vai trò
- **Admin:** doanh thu, học viên mới, lượt đặt, tỷ lệ hủy (30 ngày); lớp sắp tới, lớp hôm nay, yêu cầu hủy chờ duyệt, đơn đã thanh toán trong tháng
- **Lễ tân:** lớp hôm nay, học viên hôm nay, yêu cầu Private và trị liệu chờ duyệt, đơn chờ thanh toán. Không có doanh thu
- **HLV:** chỉ số liệu của mình: lớp hôm nay, học viên hôm nay, chưa điểm danh, yêu cầu hủy chờ duyệt. Không có doanh thu

## Acceptance criteria

- [x] Doanh thu chỉ tính đơn đã thanh toán trong kỳ, theo giá trên đơn dù gói đổi giá
- [x] Chuỗi theo ngày đủ cả ngày không có số liệu
- [x] Breakdown gộp đúng theo sản phẩm, đếm đúng số vé
- [x] Học viên mới chỉ tính lần mua gói đầu tiên
- [x] Lấp đầy chỉ tính buổi đã diễn ra, không tính buổi hủy và lượt hủy
- [x] Khoảng thời gian sai báo 422
- [x] Báo cáo chỉ admin xem được; lễ tân, HLV không thấy doanh thu ở Tổng quan

Test: `tests/Feature/Gym/ReportFlowTest.php`.

## Quyết định

- 2026-11-02 — Học viên mới là người mua gói đầu tiên trong kỳ
- 2026-11-02 — Chưa làm xuất Excel, PDF

## Liên quan

- [orders](../orders/spec.md), [bookings](../bookings/spec.md), [events](../events/spec.md), [redlight](../redlight/spec.md)
