# Spec: Admin xem chi tiết user + lịch sử thanh toán

## Bối cảnh

Admin cần xem thông tin chi tiết một user kèm subscription hiện tại và lịch sử giao dịch IAP (Google/Apple). Hiện `UserController` chỉ có `index`, `destroy`, `export`. Cần thêm `show`.

## Phạm vi

### In-scope
- Trang chi tiết user (`GET /admin/users/{user}`) — action `show` thêm vào `UserController`
- Card thông tin user: id, tên, email, SĐT, ngày sinh, ngày tạo, trạng thái tài khoản (deleted hay không)
- Card subscription hiện tại: lấy từ `subscriptions` (user_id UNIQUE, 1 record/user). Hiển thị: plan, provider, status (badge), amount + currency, billing_cycle, ngày mua (`created_at`), ngày hết hạn (`expires_at`), auto_renew, cancelled_at. Nếu chưa có → thông báo "Chưa có subscription"
- Bảng lịch sử giao dịch: union `google_subscriptions` + `apple_subscriptions` theo `user_id`, sort `created_at` DESC. Mỗi dòng: provider, transaction_id (order_id/transaction_id), product_id (item_id/product_id), ngày giao dịch, ngày hết hạn, status
- Link "Xem chi tiết" trong dropdown action ở list user (+ click vào tên/email cũng dẫn vào show)

### Out-of-scope
- Sửa subscription từ admin
- Lịch sử subscription ở bảng `subscriptions` (user_id UNIQUE → 1 dòng duy nhất)
- `raw_response` JSON detail

## Nghiệp vụ

1. Admin vào list `/admin/users` → click tên/email hoặc "Xem chi tiết" → vào `/admin/users/{user}`
2. Trang show gồm 3 card:
   - **Thông tin khách hàng**: thông tin cơ bản + plan + subscription_status từ `users` table
   - **Subscription hiện tại**: dữ liệu từ `subscriptions` (nếu có), dùng badge màu cho status
   - **Lịch sử giao dịch**: merge `google_subscriptions` + `apple_subscriptions` của user, sort `created_at` DESC
3. Không phân trang lịch sử (số giao dịch thường nhỏ)

## Input / Output

### Output — Thông tin user
- `id`, `first_name`, `last_name`, `email`, `phone`, `dob`, `created_at`, `subscription_status` (badge), `plan` (badge)

### Output — Subscription hiện tại
- `plan` (badge), `provider`, `status` (badge), `amount`, `currency`, `billing_cycle`, `created_at`, `expires_at`, `auto_renew`, `cancelled_at`, `grace_period_ends_at`, `trial_ends_at`

### Output — Lịch sử giao dịch (mỗi dòng)
- Provider (`Google IAP` / `Apple IAP`)
- Transaction ID (`order_id` / `transaction_id`)
- Product ID (`item_id` / `product_id`)
- Ngày giao dịch (`transaction_date` / `purchase_date`)
- Ngày hết hạn (`expiry_date` / `expires_date`)
- Status (badge)
- Ngày tạo record (`created_at`)

## Acceptance criteria

- [ ] `/admin/users/{user}` trả trang show, breadcrumb đúng
- [ ] Card user hiển thị đầy đủ thông tin, badge plan/status
- [ ] Card subscription: nếu có → hiển thị đầy đủ; nếu không có → "Chưa có subscription"
- [ ] Bảng lịch sử: merge google + apple, sort created_at DESC, "Chưa có giao dịch" khi trống
- [ ] Link "Xem chi tiết" trong dropdown list user → đúng route
- [ ] Click tên/email trong list user → vào trang show
- [ ] Toàn bộ label tiếng Việt

## Quyết định

- 2026-06-02 — Entry vào show → cả dropdown "Xem chi tiết" + click tên/email trong list
- 2026-06-02 — Lịch sử giao dịch → union google_subscriptions + apple_subscriptions theo user_id
- 2026-06-02 — Không phân trang lịch sử giao dịch (số lượng nhỏ)

## Liên quan

- `app/Admin/Http/Controllers/UserController.php`
- `app/Share/Models/Subscription.php`, `GoogleSubscription.php`, `AppleSubscription.php`
- `resources/views/admin/users/index.blade.php`
- `routes/admin.php`
