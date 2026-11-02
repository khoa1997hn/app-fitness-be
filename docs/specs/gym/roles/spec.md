# Spec: Role và phân quyền nhân viên

## Bối cảnh

Web phòng tập có lễ tân, huấn luyện viên, admin dùng chung React. Bảng `admins` chỉ phục vụ Blade của app video.

## Phạm vi

### In-scope
- Cột `users.role`: `admin`, `receptionist`, `trainer`, `member`, mặc định `member`
- `GET /auth/profile` trả thêm `role`
- Middleware `permission:<nhóm>.<hành động>`, ma trận ở `config/permissions.php`, deny by default
- Seeder tài khoản demo theo role

### Out-of-scope
- Khóa tài khoản (`users.status`), màn tạo nhân viên, đổi role: phase 7
- Cấp admin đầu tiên trên production

## Nghiệp vụ

- User app hiện có nhận role `member`; hành vi app không đổi, `/auth/profile` chỉ thêm field
- Đăng ký công khai luôn là `member`, không chọn được role
- Chưa đăng nhập: 401. Không đủ quyền: 403

## Acceptance criteria

- [x] Đăng ký kèm `role=admin` vẫn tạo `member`
- [x] Profile có `role`, giữ nguyên field cũ
- [x] Permission không khai báo thì từ chối, kể cả admin

Test: `tests/Feature/Gym/RoleTest.php`.

## Quyết định

- 2026-11-02 — Lưu tài khoản nhân viên ở đâu → một bảng `users` + `role`
- 2026-11-02 — `pt` và `instructor` → gộp thành `trainer`, nhận quyền của `pt`

## Liên quan

- Ma trận đầy đủ: [`fitness-web/docs/auth/permissions.md`](../../../../../fitness-web/docs/auth/permissions.md)
