# Kế hoạch báo cáo

## Update 2026-11-07

- Dùng permission hiện có: `dashboard.view` và `reports.view` chỉ admin; không thêm endpoint hay migration.
- Dashboard nhận `from/to`, dùng validation chung của reports. Giữ response `card[]`.
- Mở rộng `/reports/classes` với `subjects`, `time_slots`, `dates`, `weekdays`; mỗi dòng có `attended` ngoài các field cũ.
- Overview/members thêm `staff_cancellations`, `member_cancellations` và các tỷ lệ tương ứng; `cancelled_by` xác định nhóm.
- Dùng `withCount` cho lượt đặt/điểm danh, cùng bộ lọc lớp đã kết thúc; tổng hợp theo nhóm trong ReportService.
- Dùng chung bộ lọc ngày ở Dashboard/Báo cáo, hiện trạng thái tải/lỗi và biểu đồ có nhãn số liệu.
- Kiểm tra: frontend build, ESLint, test quyền/redirect/mốc tuần tháng; backend regression cho ngày, số liệu, 403.
