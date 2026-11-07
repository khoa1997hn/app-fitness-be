## Update 2026-11-07

- [x] spec-analyzer / question-asker: đọc spec cũ, chốt cả ảnh bìa và inline cùng thông số upload với PO.
- [x] solution-reviewer / api-designer / api-analyzer: giữ presigned S3, quyền events.manage và endpoint detail hiện có; key ảnh lâu dài, URL cấp khi đọc.
- [x] planner / task-breaker: cập nhật kế hoạch và checklist, không đổi nghiệp vụ vé.
- [x] implementer: migration/model/config/endpoint/sanitizer, editor/resize/upload/renderer/detail.
- [x] openapi-writer: schema ảnh/HTML/upload và regenerate Swagger.
- [x] reviewer-rules / reviewer-smell / reviewer-security / reviewer-duplicate: rà soát validation, quyền upload, sanitizer, key path và renderer chung.
- [x] cleaner / docs-syncer: bỏ import thừa, lazy load editor, giữ lockfile Yarn đúng định dạng; đồng bộ spec và screen docs.
- [x] finalizer: hoàn tất kiểm tra và ghi kết quả, dừng trước commit.

### Kết quả và triển khai

- EventFlowTest: 20 tests, 158 assertions pass trên MySQL local và SQLite memory. FE build, 8 tests i18n và ESLint phần thay đổi pass; lint toàn repo còn lỗi cũ ngoài Event.
- Browser với API/S3 giả lập: tạo với cover/inline, định dạng/preview, lưu key ảnh và mở detail; 3200×2000 thành 1600×1000 WebP, không phóng lớn ảnh nhỏ, từ chối SVG và giữ văn bản cũ.
- Migration đã chạy local; Pint và Swagger đã cập nhật. Server cần cài dependencies FE/BE, build FE và chạy migration.
- LocalStack hiện thiếu LOCALSTACK_AUTH_TOKEN; chưa xác minh upload vào S3 thật tại local. PO sẽ test trên server và yêu cầu không kiểm thử thêm.
- Không commit/push.
