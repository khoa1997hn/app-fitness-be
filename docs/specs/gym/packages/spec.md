# Spec: Danh mục gói tập

## Bối cảnh

Gói buổi tập bán trên web, thay mock `fitness-web/src/mocks/packages.ts`. Khác hoàn toàn `Plan`/`Subscription` của app video.

Screen doc: [`package-management.md`](../../../../../fitness-web/docs/screens/admin/package-management.md), [`packages.md`](../../../../../fitness-web/docs/screens/student/packages.md).

## Phạm vi

### In-scope
- `GET /packages`, `GET /packages/{id}`: công khai, chỉ gói `active`
- `GET|POST /admin/packages`, `GET|PATCH|DELETE /admin/packages/{id}`, `PATCH /admin/packages/{id}/status`: quyền `packages.manage`, chỉ admin

### Out-of-scope
- Mua gói, `user_packages`: phase 3
- Chặn xóa gói đã bán: phase 3, vì chưa có `user_packages`
- Lọc lịch theo off-peak: phase 4, chờ PO chốt khung giờ

## Nghiệp vụ

- `package_type`: `drop_in`, `class_pack`, `unlimited`, `intro_demo`, `private_pack`, `private_group`
- `unlimited` luôn lưu `class_credits = null`; loại khác bắt buộc có số buổi
- `base_people_max` mặc định 2, `extra_people_session_max` mặc định 100, cấu hình theo từng gói
- `name`, `description` theo locale (rule 14); field khác dùng chung
- Gói `hidden` không hiện với hội viên, trang chi tiết trả 404

## Acceptance criteria

- [x] Danh sách công khai chỉ có gói `active`
- [x] Lễ tân, HLV, hội viên nhận 403 ở mọi API admin; chưa đăng nhập nhận 401
- [x] Admin tạo, sửa, ẩn, xóa được
- [x] Validate số buổi theo loại gói
- [x] Sửa ở locale `en` không ghi đè bản `vi`

Test: `tests/Feature/Gym/PackageApiTest.php`.

## Quyết định

- 2026-11-02 — Bỏ `visible_on` trong screen doc: app không đọc gói phòng tập, mock luôn "Web + App"
- 2026-11-02 — Không thêm `created_by`, `updated_by`: chưa có màn dùng, chờ Q11 ở [`01-docs-verification.md`](../../../../../fitness-web/plan/01-docs-verification.md)

## Liên quan

- [roles](../roles/spec.md)
