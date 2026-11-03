# Admin tập trung trên React

Hai sản phẩm dùng chung React admin (`fitness-web`). Nút **Quản lý app** mở menu con cho app video, chỉ role admin. Phòng tập giữ các menu và phân quyền hiện có.

| | App video | Phòng tập |
|---|---|---|
| Sản phẩm | App video | Hệ thống phòng tập offline |
| Module | Program, Lesson, Banner, Combo, User + subscription | Gói tập, lịch, booking, điểm danh, thanh toán, sự kiện, user, báo cáo |
| Auth | JWT `auth:api`, `users.role` | JWT `auth:api`, `users.role` |
| Quyền | `permission:app.manage`, chỉ admin | Middleware `permission:<nhóm>.<hành động>`, `config/permissions.php` |
| Route | `routes/api.php`, prefix `/api/v1/admin/app` | `routes/api.php`, prefix `/api/v1/admin` |
| Controller | `app/Web/Http/Controllers/API/V1/AppAdmin/` | `app/Web/Http/Controllers/API/V1/Gym/Admin/` |
| Spec | `docs/specs/admin/` | `docs/specs/gym/` + `fitness-web/docs/screens/admin/` |

Lịch sử: 2026-11-02 tách Blade/React. Quyết định 2026-11-03 thay thế: chuyển toàn bộ quản trị app video sang React, ngừng route Blade `/admin` và đăng nhập session cũ. Spec cập nhật: `docs/specs/admin/program-management/spec.md`.

Nguồn tài khoản được lưu bằng `users.registration_source`; nhân viên tạo lưu thêm `created_by`, source giữ vai trò lúc tạo. Native đăng ký thiếu source mặc định app, React gửi web. Backfill lịch sử trước 2026-11-02 là app, từ ngày đó là web; không xóa hay tạo lại tài khoản.

Ma trận: [`fitness-web/docs/admin/module-matrix.md`](../../../fitness-web/docs/admin/module-matrix.md).

## Thêm API admin phòng tập

1. Thêm key vào `config/permissions.php`, khớp `fitness-web/docs/auth/permissions.md`
2. Route trong group `admin` của `routes/api.php`, bọc `permission:<key>`
3. Dữ liệu chỉ thuộc về người thực hiện (ví dụ lịch của HLV): kiểm tra thêm trong controller, middleware quyền không đủ
4. Test role bị từ chối, không chỉ role được phép
