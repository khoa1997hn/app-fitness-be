# Spec: Admin sửa / tạo subscription khách hàng

## Bối cảnh

Admin cần chỉnh subscription của user từ trang Chi tiết khách hàng (hỗ trợ cấp gói thủ công hoặc sửa trạng thái/hết hạn). Xem subscription: spec [`user-subscription-view`](../user-subscription-view/spec.md).

## Phạm vi

### In-scope
- Nút **Sửa subscription** / **Tạo subscription** trên card subscription tại `GET /admin/users/{user}`
- Form `GET /admin/users/{user}/subscription/edit` — 4 field: `plan`, `status`, `expires_at`, `auto_renew`
- Submit `PUT /admin/users/{user}/subscription` qua `SubscriptionService::adminUpsert` (transaction + lock + sync `users.plan` + `users.subscription_status`)
- Tạo mới khi chưa có record: `provider=admin`, `amount` từ `config('app_payment.plans.<plan>.price')`, `currency`/`billing_cycle` mặc định
- Sửa record có sẵn: chỉ 4 field form; giữ `provider`, `amount`, `currency`, `billing_cycle`, các ngày khác

### Out-of-scope
- Sửa `google_subscriptions` / `apple_subscriptions`
- Field trial/cancelled/grace, amount, provider trên form
- `raw_response` / lịch sử IAP

## Nghiệp vụ

1. Admin vào chi tiết user → bấm Sửa/Tạo subscription
2. Điền form → Lưu → redirect chi tiết + flash success
3. `users.plan` và `users.subscription_status` luôn khớp subscription sau lưu

## Input / Output

### Input (form)
| Field | Bắt buộc | Ghi chú |
|-------|----------|---------|
| `plan` | Có | `basic`, `plus`, `all` |
| `status` | Có | `SubscriptionStatus` values |
| `expires_at` | Không | datetime |
| `auto_renew` | Không | checkbox boolean |

### Output
- Redirect `admin.users.show` + message tiếng Việt

## Acceptance criteria

- [ ] Nút Sửa/Tạo trên card subscription
- [ ] Form 4 field, validate tiếng Việt, `old()` khi lỗi
- [ ] Tạo mới → provider Admin, amount theo config plan
- [ ] Sửa IAP → provider/amount không đổi; plan/status/expires/auto_renew cập nhật
- [ ] Card user (plan/status) đồng bộ sau lưu
- [ ] Log channel `subscription` với `admin_id`

## Quyết định

- 2026-06-04 — Provider tạo từ admin: `admin` (`SubscriptionProvider::Admin`)
- 2026-06-04 — Logic qua `SubscriptionService::adminUpsert`, không update từ controller

## Liên quan

- `app/Admin/Http/Controllers/UserController.php`
- `app/Share/Services/Subscription/SubscriptionService.php`
- `resources/views/admin/users/show.blade.php`
- `resources/views/admin/users/subscription/edit.blade.php`
- `routes/admin.php`
