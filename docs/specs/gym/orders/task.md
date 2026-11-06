
## Update 2026-11-06

- [x] Phân tích yêu cầu, nghiệp vụ và endpoint hiện có; cập nhật spec.
- [x] Mở rộng huỷ đơn chờ, kiểm tra chủ đơn và khóa dòng khi ký/huỷ/thanh toán.
- [x] Thêm UI xác nhận, trạng thái đang huỷ, lỗi và tải lại danh sách.
- [x] Đồng bộ OpenAPI và tài liệu màn hình.
- [x] Review quyền sở hữu, trạng thái đơn, khóa dòng, luồng trả vé và code thừa.
- [x] Kiểm tra: 63 tests / 383 assertions (OrderFlowTest, EventFlowTest, RedlightFlowTest); build frontend; lint các file thay đổi; Pint; sinh Swagger.
- Lint toàn frontend còn 8 lỗi và 2 cảnh báo trong các file không thay đổi.
- Test dùng cấu hình container MySQL qua biến môi trường, không sửa `.env`; cổng host MySQL 33306 do 3306 đang được sử dụng.
