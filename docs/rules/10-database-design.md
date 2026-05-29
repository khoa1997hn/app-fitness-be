# Database design

Triết lý: **đúng và đủ**. Không bịa field, không phòng xa.

## 1. Field tối thiểu

Chỉ thêm field thực sự cần cho nghiệp vụ trong spec. Mỗi field phải trả lời được:

- Field này dùng ở đâu trong code/UI ngay trong spec này? Nếu không có chỗ dùng → KHÔNG thêm.
- Spec có yêu cầu field này không? Nếu không → hỏi user, KHÔNG bịa.

## 2. Field thường bị thêm dư (CẤM thêm nếu spec không yêu cầu)

- `created_by`, `updated_by`, `deleted_by` — chỉ thêm khi spec có audit/permission yêu cầu.
- `deleted_at` (soft delete) — chỉ thêm khi spec yêu cầu giữ lịch sử / có khả năng restore.
- `status` / `is_active` / `is_published` — chỉ thêm khi spec có ≥ 2 trạng thái. Một trạng thái → KHÔNG cần.
- `order` / `sort_order` / `position` — chỉ thêm khi spec yêu cầu sort tùy chỉnh được người dùng.
- `meta` / `extra` / `data` (JSON) — chỉ thêm khi đã biết rõ field nào sẽ vào đó. Cấm dùng làm "túi đựng linh tinh".
- `slug` — chỉ thêm khi có URL public dùng slug.
- `uuid` thay cho `id` — chỉ thêm khi có lý do bảo mật/distributed cụ thể.
- `note` / `description` thừa — nếu spec không hiển thị → không thêm.

## 3. Field BẮT BUỘC giữ

- `id` (auto-increment) — luôn có.
- `created_at`, `updated_at` — giữ mặc định của Laravel (`$table->timestamps()`). Nếu spec yêu cầu KHÔNG có timestamps → mới bỏ.

## 4. Nullable

- Mặc định `NOT NULL`. Chỉ đặt `nullable()` khi nghiệp vụ cho phép value có thể không tồn tại.
- Không dùng nullable để "linh hoạt phòng khi" — quyết định null hay not-null thuộc nghiệp vụ.

## 5. Kiểu dữ liệu

- Chọn kiểu nhỏ nhất đủ dùng:
  - Cờ boolean → `boolean()` (KHÔNG `tinyInteger()` rồi ép kiểu).
  - Số tự nhiên < 65k → `unsignedSmallInteger()`. Đếm bình thường → `unsignedInteger()`. ID lớn → `unsignedBigInteger()`.
  - Tiền → `decimal(p, s)` đủ phạm vi (KHÔNG `float`).
  - Text ngắn cố định → `string(n)` với `n` hợp lý.
  - Text dài → `text()`. Cực dài → `longText()`.
  - Enum giá trị cố định → `string` + ràng buộc qua Enum class trong PHP (xem `docs/rules/02-code-quality.md`).

## 6. Index

- Chỉ thêm index khi có query thực tế dùng cột đó để lọc/sort/join.
- Cấm thêm index "phòng xa" cho mọi cột.
- Foreign key → BẮT BUỘC kèm index (Laravel `foreignId()->constrained()` tự tạo).

## 7. Foreign key

- Chỉ thêm FK tới bảng đã tồn tại VÀ relationship đã có trong spec.
- Đặt `onDelete()` rõ ràng theo nghiệp vụ:
  - User xóa account → posts của họ giữ hay xóa? Quyết định trong spec, KHÔNG mặc định.
  - Default Laravel là `RESTRICT` → an toàn nhưng phải có quyết định.

## 8. Đặt tên

- Table: số nhiều, snake_case (`users`, `subscription_plans`).
- Column: snake_case, danh từ rõ nghĩa.
- FK column: `<singular_table>_id` (ví dụ `user_id`).
- Boolean: prefix `is_`, `has_`, `can_` (ví dụ `is_active`, `has_paid`).
- Datetime: suffix `_at` (`published_at`, `expires_at`).
- Date: suffix `_date` (`birth_date`).
- KHÔNG dùng tên mơ hồ: `data`, `info`, `value`, `extra`.

## 9. Migration

- BẮT BUỘC tạo qua `php artisan make:migration` (xem `docs/rules/06-docker-sail.md`).
- 1 migration = 1 thay đổi logic (tạo bảng / thêm cột / đổi cột). KHÔNG gộp nhiều bảng không liên quan vào 1 migration.
- Migration đã chạy production → KHÔNG sửa file gốc, tạo migration mới để thay đổi.
- Tên file mô tả rõ action: `create_users_table`, `add_email_verified_at_to_users_table`.

## 10. Trước khi viết migration

Checklist:

- [ ] Mỗi field đều có chỗ dùng trong spec này?
- [ ] Đã loại các field "dư" ở mục 2?
- [ ] Kiểu dữ liệu là nhỏ nhất đủ dùng?
- [ ] Nullable phản ánh đúng nghiệp vụ?
- [ ] Index chỉ thêm cho cột có query thực?
- [ ] FK kèm `onDelete()` quyết định rõ?
- [ ] Tên cột/bảng theo convention?

Nếu BẤT KỲ field nào không trả lời được → DỪNG, hỏi user (xem `docs/rules/00-core.md` và `docs/guides/ask-protocol.md`).
