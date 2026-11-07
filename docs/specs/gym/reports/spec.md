# Spec: Báo cáo và tổng quan

## Bối cảnh

Admin xem doanh thu, học viên, lớp học và trang Tổng quan. Screen doc: `fitness-web/docs/screens/admin/reports.md`.

## Phạm vi

### In-scope
- `reports.view` (admin): `GET /admin/reports/overview|revenue|members|classes?from=&to=`
- `dashboard.view` (chỉ admin): `GET /admin/dashboard?from=&to=`

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

### Tổng quan admin (cập nhật 2026-11-07)
- Chỉ admin xem được Dashboard, báo cáo và gọi trực tiếp các API dữ liệu tương ứng; PT/lễ tân nhận 403. Frontend ẩn menu và chặn URL, chuyển về Quản lý lịch.
- Bộ lọc chung: hôm nay, tuần này (thứ Hai đến hôm nay), tháng này, 30 ngày và khoảng ngày tùy chọn; theo giờ Việt Nam.
- Thẻ số liệu trong kỳ: doanh thu, học viên mới, lượt đặt, tỷ lệ hủy chung, nhân viên hủy, học viên tự hủy, buổi học không hủy, đơn thanh toán.
- Biểu đồ phân theo bộ môn (`FitnessClass.category`), khung giờ bắt đầu–kết thúc, ngày và thứ ISO (1–7); chỉ tính buổi gói thường đã kết thúc, không hủy.
- Mỗi nhóm có số buổi, lượt đặt còn hiệu lực, lượt điểm danh có mặt và lấp đầy trung bình. Có thể chọn chỉ số cho biểu đồ; ngày/giờ/thứ sắp theo thời gian.
- Hủy phân theo người bấm: `cancelled_by = user_id` là học viên tự hủy; khác là nhân viên hủy, kể cả hủy hộ. Không phụ thuộc role hiện tại. Bản ghi thiếu người hủy chỉ nằm trong tỷ lệ chung.
- Số và tỷ lệ hủy vẫn dựa trên booking tạo trong kỳ; mỗi tỷ lệ dùng cùng mẫu số tổng lượt đặt. Không bao gồm Private/trị liệu.

## Acceptance criteria

- [x] Doanh thu chỉ tính đơn đã thanh toán trong kỳ, theo giá trên đơn dù gói đổi giá
- [x] Chuỗi theo ngày đủ cả ngày không có số liệu
- [x] Breakdown gộp đúng theo sản phẩm, đếm đúng số vé
- [x] Học viên mới chỉ tính lần mua gói đầu tiên
- [x] Lấp đầy chỉ tính buổi đã diễn ra, không tính buổi hủy và lượt hủy
- [x] Khoảng thời gian sai báo 422
- [x] Dashboard và mọi báo cáo chỉ admin xem được; lễ tân/HLV bị chặn cả URL và API

Test: `tests/Feature/Gym/ReportFlowTest.php`.

## Quyết định

- 2026-11-02 — Học viên mới là người mua gói đầu tiên trong kỳ
- 2026-11-02 — Chưa làm xuất Excel, PDF

- 2026-11-07 — PO chọn cả lượt đặt, điểm danh có mặt, tỷ lệ lấp đầy; hủy theo người bấm (nhân viên/học viên).

## Liên quan

- [orders](../orders/spec.md), [bookings](../bookings/spec.md), [events](../events/spec.md), [redlight](../redlight/spec.md)
