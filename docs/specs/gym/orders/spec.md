# Spec: Đơn hàng và thanh toán mock

## Bối cảnh

Hội viên mua gói tập, trị liệu trên web. Khách tại quầy được nhân viên tạo tài khoản và đơn hộ, rồi tự ký và thanh toán.

Screen doc: `fitness-web/docs/screens/student/payment.md`, `admin/payment-management.md`.

## Phạm vi

### In-scope
- Catalog trị liệu: `GET /therapy-services[/{slug}]`, công khai
- Hội viên (`student.portal`): `POST /orders`, `GET /orders/{code}`, `POST /orders/{code}/accept-terms`, `POST /payments`, `GET /users/me/orders|packages|therapies`
- Nhân viên: `GET|POST /admin/members`, `GET /admin/members/{id}` (`users.manage`); `POST /admin/users/{id}/orders` (`orders.create`); `GET /admin/payments[/{id}]` (`payments.manage`)
- Chặn xóa gói đã có đơn, trả `can_delete`

### Out-of-scope
- Đơn sự kiện, tiện ích, khóa HLV: phase 8, 9, deferred
- Cổng thanh toán thật, webhook, hết hạn đơn, đối soát thủ công, hoàn tiền
- Trừ buổi: phase 4, 5
- Gia hạn gói: phase 7

## Nghiệp vụ

Trạng thái đơn: `pending_terms` → (ký) → `pending` → (thanh toán) → `success`.

- Giá lấy từ server và chốt lúc tạo đơn. Gói ẩn không tạo đơn được, kể cả nhân viên
- Đơn chỉ thuộc hội viên; đơn người khác trả 404
- Thanh toán chạy trong transaction có khóa dòng, gọi lại không cấp gói lần hai
- Cấp gói chụp lại thông tin gói; `end_date = hôm nay + duration_days`. Trị liệu: `GYM_THERAPY_VALIDITY_DAYS`, mặc định 90 ngày
- Mock tắt mặc định khi `APP_ENV=production`, vì mock cấp gói miễn phí
- Chưa có bảng `payments`: thanh toán mock chỉ có một kết quả nên lưu ngay trên `orders`. Thêm bảng khi có cổng thật
- Chữ ký lưu data URL trên `orders.terms_signature`, tối đa 700 KB. Cân nhắc chuyển S3 trước production
- Học viên ký vào ô chữ ký trên file PDF điều khoản. Server chỉ lưu ảnh chữ ký, bản PDF có chữ ký được tạo lúc gửi email thanh toán thành công và không lưu lại
- Bản sao in thêm họ tên người ký (`buyer_name`) và ngày ký (`terms_accepted_at`) trên trang chữ ký
- Thay file điều khoản: thay `resources/terms/dieu-khoan-dich-vu.pdf`, chỉnh vị trí ô chữ ký qua `GYM_TERMS_SIGNATURE_PAGE|X|Y|WIDTH|HEIGHT` và vị trí họ tên, ngày ký qua `GYM_TERMS_SIGNER_X|Y|WIDTH` (tỉ lệ trang từ 0 đến 1, gốc trên trái), rồi chạy `TermsDocumentTest`
- File phải là PDF 1.4 vì thư viện FPDI bản miễn phí không đọc PDF nén kiểu mới. Chuyển đổi bằng `gs -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -o moi.pdf goc.pdf`

### Tại quầy

Nhân viên tìm khách hoặc tạo tài khoản (đặt mật khẩu tạm, role luôn `member`) → tạo đơn → khách đăng nhập tài khoản của mình → ký → thanh toán, giống hệt đơn tự đặt.

## Acceptance criteria

- [x] Không thanh toán được khi chưa ký
- [x] Giá gửi từ client bị bỏ qua
- [x] Thanh toán lặp không cấp gói hai lần
- [x] Người khác không đọc hay thao tác được đơn
- [x] Nhân viên không tạo đơn được cho tài khoản nhân viên; hội viên không gọi được API nhân viên
- [x] Lễ tân, HLV, admin đều chạy được luồng tại quầy

Test: `tests/Feature/Gym/OrderFlowTest.php`.

## Quyết định

- 2026-11-02 — Thanh toán tại quầy → HLV/lễ tân tạo đơn trên web nội bộ, tạo tài khoản hộ nếu cần, khách ký và thanh toán như đơn online
- 2026-11-02 — Vật phẩm mua thêm → chỉ có Redlight và slot thêm người, không quản lý tồn kho, nên không cần `order_items`

## Liên quan

- [packages](../packages/spec.md), [roles](../roles/spec.md)
