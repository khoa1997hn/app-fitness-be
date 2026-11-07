## Update 2026-11-07

1. Giữ luồng CRUD/vé, bổ sung FileType/config và cột ảnh nullable trong translation.
2. Endpoint presigned riêng được bảo vệ bởi events.manage; tái dùng request/service upload hiện có.
3. EventContentService lọc HTML và chuyển key S3 ↔ URL có hạn, dùng chung cho mapping public/admin.
4. FE dùng Tiptap + DOMPurify, resize/nén WebP qua Canvas trước upload; ảnh bìa, ảnh nội dung, xem trước.
5. Chi tiết học viên bằng modal tải endpoint công khai sẵn có; admin dùng renderer chung.
6. Test regression Event + ảnh/XSS/quyền; kiểm tra browser resize/editor/detail, build/lint phần thay đổi, Swagger và Pint.
