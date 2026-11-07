# Spec: Sự kiện, vé và check-in

## Bối cảnh

Hội viên đăng ký sự kiện Bloom, admin quản lý sự kiện và check-in tại cổng. Screen doc: `fitness-web/docs/screens/student/events.md`, `admin/event-management.md`.

## Phạm vi

### In-scope
- Công khai: `GET /events`, `GET /events/{id}`
- `student.portal`: `POST /events/{id}/register` (vé miễn phí), `POST /orders` với `type=event`, `GET /users/me/event-registrations`
- `events.manage`: `GET|POST /admin/events`, `GET|PATCH /admin/events/{id}`, `PATCH .../status`, `GET .../registrations`, `PATCH /admin/registrations/{id}/checkin`
- Command `gym:release-event-holds` chạy mỗi phút

### Out-of-scope
- Quét QR check-in: admin bấm nút trên danh sách
- Học viên tự hủy vé
- Hoàn tiền tự động

## Nghiệp vụ

### Số vé
- Không lưu riêng, tính từ `event_registrations`. `tickets_left` = tổng − đã bán − đang giữ
- Khóa dòng sự kiện khi đăng ký để không bán vượt
- Mỗi hội viên một lượt đăng ký còn hiệu lực cho một sự kiện, tối đa 20 vé một lượt
- Sửa `total_tickets` không được nhỏ hơn số đã bán và đang giữ

### Vé miễn phí
Đăng ký xong có mã vé ngay, `status=paid`, không có đơn hàng.

### Vé có phí
- Tạo đơn: **giữ vé ngay, trong 15 phút** (`GYM_EVENT_HOLD_MINUTES`), kể cả khi chưa ký điều khoản
- Ký, thanh toán trong thời hạn: `status=paid`, gửi thông báo kèm mã vé
- Quá hạn: vé thôi tính ngay, không ký và không thanh toán được nữa. Command chuyển vé sang `cancelled`, đơn sang `cancelled` và báo cho hội viên
- `ticket_code`: `EVT-{event_id}-{registration_id}`

### Trạng thái sự kiện
- `open`: bán vé. Danh sách công khai chỉ gồm sự kiện `open` chưa diễn ra
- `closed`: ngừng nhận đăng ký mới, vé đang giữ vẫn thanh toán được, có thể mở lại
- `cancelled`: trạng thái cuối. Mọi vé bị hủy, đơn chưa thanh toán bị hủy, hội viên nhận thông báo kèm lý do. Vé đã thanh toán có `needs_refund=true`, hoàn tiền thủ công. Không sửa được nữa

### Check-in
Chỉ vé `paid`, mỗi vé một lần. Check-in theo cả lượt đăng ký, không theo từng vé trong lượt.

### Khác
- Học viên tự hủy được đơn vé **chưa thanh toán** (`POST orders/{code}/cancel`): vé và đơn sang `cancelled`, vé trả lại ngay, không gửi thông báo. Vé đã thanh toán chưa tự hủy được, vì hoàn tiền còn thủ công
- Đơn vé chưa thanh toán trả `hold_expires_at` để FE đếm ngược
- Nhắc lịch trước sự kiện qua `gym:send-reminders`
- Tài khoản đã có vé sự kiện không xóa được, chỉ khóa

## Acceptance criteria

- [x] Danh sách công khai không cần đăng nhập, chỉ gồm sự kiện mở chưa diễn ra
- [x] Vé đang giữ chặn bán vượt; quá hạn thì người khác mua được
- [x] Giữ chỗ quá hạn không ký, không thanh toán được; nhả vé chạy lại an toàn
- [x] Vé miễn phí và có phí đi đúng luồng; không đăng ký trùng
- [x] Không giảm số vé xuống dưới số đã bán và đang giữ
- [x] Check-in chỉ vé đã thanh toán, một lần
- [x] Hủy sự kiện: hủy vé, thông báo, đánh dấu cần hoàn tiền; không mở lại, không sửa được
- [x] Đóng sự kiện: vé đang giữ vẫn thanh toán được; mở lại bán tiếp

Test: `tests/Feature/Gym/EventFlowTest.php`.

## Quyết định

- 2026-11-02 — Giữ vé ngay khi tạo đơn, 30 phút, quá hạn tự nhả
- 2026-11-02 — Học viên chưa tự hủy được vé trong giai đoạn đầu

## Liên quan

- [orders](../orders/spec.md), [users-account](../users-account/spec.md)

## Update 2026-11-07 — Ảnh và nội dung sự kiện

### Quyết định

- PO chọn cả ảnh bìa riêng và ảnh chèn trong mô tả.
- JPG/PNG/WebP; frontend resize giữ tỷ lệ, không phóng lớn, cạnh dài tối đa 1600px và nén WebP chất lượng 0.82. Nếu browser không encode WebP thì dùng PNG đã resize. Backend giới hạn 5MB mỗi ảnh; prefix `event/image`.
- Ảnh bìa và ảnh trong nội dung dùng chung `FileType::EventImage` vì cùng loại nội dung, định dạng, giới hạn và nơi lưu.
- Ảnh bìa tùy chọn nằm trong `event_translations.image` (jsonb + FileCast), theo cùng locale với mô tả. Giữ cách ghi locale vi đang có của Event admin.
- Editor Tiptap: tiêu đề h2/h3, đậm/nghiêng/gạch chân, danh sách, trích dẫn, liên kết, undo/redo, upload ảnh và xem trước. Giữ giới hạn mô tả 5000 ký tự (gồm HTML).
- Backend lọc HTML bằng HTMLPurifier; frontend lọc lần nữa bằng DOMPurify khi hiển thị. Mô tả văn bản cũ vẫn hiển thị xuống dòng.
- Không lưu URL S3 có hạn trong mô tả: lưu `img.src` bằng key S3, chỉ chấp nhận key ảnh Event do luồng presigned sinh ra. API cấp lại URL và kèm `data-file-path` khi đọc; FE giữ key khi soạn và gửi lại.
- Card học viên có ảnh bìa lazy load và trích đoạn văn bản; nút Xem chi tiết mở modal tải `GET /events/{id}` để đọc toàn bộ mô tả và thông tin thời gian, địa điểm, giá, số vé còn lại. Admin detail hiển thị cùng định dạng.

### API

- `POST /admin/events/images/presigned-upload`, JWT + `events.manage`, chỉ nhận type `event_image`; dùng validation MIME/size từ `config/app_file.php`, upload trực tiếp bằng presigned PUT S3.
- Tạo/sửa Event thêm `image: {path,name,extension,size} | null`; bỏ qua image khi sửa thì giữ ảnh cũ, null để xóa. `description` nhận HTML hoặc văn bản.
- Các response Event thêm `image` metadata + URL có hạn; `description` trả HTML an toàn với URL ảnh được cấp mới.

### Acceptance criteria

- [x] Upload/thay/xóa ảnh bìa; chèn ảnh trong editor; không lưu lúc upload chưa xong.
- [x] Resize/nén trước khi xin presigned và PUT file; giữ đúng tỷ lệ, không phóng lớn ảnh nhỏ.
- [x] Ảnh trong nội dung không hỏng sau khi URL cũ hết hạn, không lưu URL có hạn vào DB.
- [x] API kiểm tra quyền, loại file, MIME, dung lượng, path ảnh và lọc HTML không an toàn.
- [x] Admin và người xem đọc được toàn bộ nội dung có định dạng; nội dung cũ vẫn dùng được.
