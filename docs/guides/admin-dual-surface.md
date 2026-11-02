# Admin: Blade và React

Hai khu vực admin thuộc **hai sản phẩm khác nhau**.

| | Blade | React (`fitness-web`) |
|---|---|---|
| Sản phẩm | App video | Hệ thống phòng tập offline |
| Module | Program, Lesson, Banner, Combo, User + subscription | Gói tập, lịch, booking, điểm danh, thanh toán, sự kiện, user, báo cáo |
| Auth | Session `auth:admin`, bảng `admins` | JWT `auth:api`, `users.role` |
| Quyền | Mọi admin | Middleware `permission:<nhóm>.<hành động>`, `config/permissions.php` |
| Route | `routes/admin.php` | `routes/api.php`, prefix `/api/v1/admin` |
| Controller | `app/Admin/Http/Controllers/` | `app/Web/Http/Controllers/API/V1/Gym/Admin/` |
| Spec | `docs/specs/admin/` | `docs/specs/gym/` + `fitness-web/docs/screens/admin/` |

Quyết định 2026-11-02: không làm React cho module app video. Spec `kore-*` cũ lưu ở `fitness-web/docs/archive/video-app/`.

Ma trận: [`fitness-web/docs/admin/module-matrix.md`](../../../fitness-web/docs/admin/module-matrix.md).

## Thêm API admin phòng tập

1. Thêm key vào `config/permissions.php`, khớp `fitness-web/docs/auth/permissions.md`
2. Route trong group `admin` của `routes/api.php`, bọc `permission:<key>`
3. Dữ liệu chỉ thuộc về người thực hiện (ví dụ lịch của HLV): kiểm tra thêm trong controller, middleware quyền không đủ
4. Test role bị từ chối, không chỉ role được phép
