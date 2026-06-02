# Plan: Admin xem chi tiết user + lịch sử thanh toán

## Phân tích ảnh hưởng

### Migration
- Không cần.

### Model
- Không đổi. Dùng `User::with('subscription', 'googleSubscription', 'appleSubscription')` qua relation có sẵn.
- Nhưng `User` không có quan hệ trực tiếp tới `GoogleSubscription`/`AppleSubscription` — sẽ query qua `GoogleSubscription::where('user_id', $user->id)` và `AppleSubscription::where('user_id', $user->id)`.

### Controller
- `UserController::show(User $user)` — mới.

### View
- `resources/views/admin/users/show.blade.php` — mới (3 card: user info, subscription, lịch sử giao dịch).
- `resources/views/admin/users/index.blade.php` — sửa: thêm link "Xem chi tiết" dropdown + tên/email clickable.

### Route
- `routes/admin.php` — thêm `show` vào resource `users`.

## Các pha

### Pha 1: Controller + Route
1. Thêm `show(User $user)` vào `UserController` — load subscription + merge google/apple transactions.
2. Thêm `show` vào resource route.

### Pha 2: Views
1. Tạo `users/show.blade.php` — 3 card.
2. Sửa `users/index.blade.php` — link "Xem chi tiết" + click tên/email.

## Verify thủ công
- [ ] Route `/admin/users/{user}` hoạt động
- [ ] User có subscription → hiển thị đầy đủ
- [ ] User không có subscription → "Chưa có subscription"
- [ ] Lịch sử merge google + apple, sort đúng
- [ ] Link từ list → show hoạt động
