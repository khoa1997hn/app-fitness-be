# Task: Admin xem chi tiết user + lịch sử thanh toán

## Pha 1: Controller + Route

- [ ] 1. Thêm `show(User $user)` vào `UserController` (load subscription, query google/apple transactions, merge sort)
- [ ] 2. Thêm `show` vào resource route `users` trong `routes/admin.php`

## Pha 2: Views

- [ ] 3. Tạo `resources/views/admin/users/show.blade.php` (3 card: user info, subscription hiện tại, lịch sử giao dịch)
- [ ] 4. Sửa `resources/views/admin/users/index.blade.php` (dropdown "Xem chi tiết" + click tên/email)
