# Spec: Hủy subscription phía provider (Google Play / Apple)

## Bối cảnh

Khi user tự xóa tài khoản (`DELETE /auth/me`), cần hủy subscription đang active phía provider để tránh trừ tiền user ở kỳ sau.
- **Google Play**: Imdhemy có sẵn `Subscription::googlePlay()->cancel()` → gọi `purchases.subscriptions.cancel` API thật.
- **Apple App Store**: Imdhemy không hỗ trợ outbound cancel → bỏ qua (user hủy thủ công qua App Store).

Ngoài ra phát hiện **bug** trong `SubscriptionService::updateSubscription()`: khi user soft-deleted, `$subscription->user` trả `null` → crash khi webhook listener gọi `->update()` sau khi user bị xóa.

## Ghi chú gốc từ user (raw, không xóa)

- vọc code hiện tại của phần app/Share/Services/Subscription/, đề xuất code thêm phần core của apple google để có thể call gọi hủy subscription (mục đích để gọi cancel subscription trong TH delete-auth, tránh TH vẫn trừ tiền KH ở tháng sau)
- Google: Như ý A, ko tự ý update cancel vào DB mà phải chờ rồi update từ webhook khi nhận event. Check lại xem webhook nếu TH user bị soft delete rồi thì có chạy ko?
- Apple: Không tự ý cancel DB vì bản chất user đã bị soft delete r, chờ webhook nhé. Check lại xem webhook nếu TH user bị soft delete rồi thì có chạy ko?
- Error handling: fail_abort (500 nếu cancel provider thất bại)
- Location: thêm vào GoogleService hiện có

## Phạm vi

### In-scope
1. **`GoogleService::cancelSubscription(Subscription $subscription): void`** — gọi Google Play cancel API thật.
2. **`SubscriptionService::updateSubscription()` bug fix** — dùng `withTrashed()` khi load user → webhook listener chạy đúng sau khi user soft-deleted.
3. **`ProfileController::destroy()`** — đổi flow: cancel provider **trước** (fail_abort nếu lỗi) → invalidate JWT → soft-delete user. Bỏ DB-cancel trực tiếp, để webhook cập nhật.

### Out-of-scope
- Apple outbound cancel API (Imdhemy không hỗ trợ; user hủy thủ công qua App Store).
- Tạo service mới riêng cho Apple cancel.
- Webhook handler logic (giữ nguyên, chỉ fix bug soft-delete user).

## Nghiệp vụ

### Flow delete account (sau update)
1. `DELETE /auth/me` nhận request.
2. Load `validSubscription` (kèm `googleSubscription`).
3. Nếu có subscription và provider = `google_iap`: gọi `GoogleService::cancelSubscription()` → Google Play dừng auto-renew.
   - Nếu API call thất bại → throw → 500, JWT vẫn hợp lệ, user không bị xóa.
4. Nếu `apple_iap` hoặc không có subscription → bỏ qua bước 3 (log nếu Apple).
5. Invalidate JWT.
6. Soft-delete user.

### Google cancel semantics
- `purchases.subscriptions.cancel`: dừng auto-renew, user **vẫn dùng** đến hết kỳ hiện tại.
- Google sẽ gửi webhook `SubscriptionCanceled` → `SubscriptionCanceledListener` cập nhật DB.

### Webhook sau khi user soft-deleted (bug fix)
- `SubscriptionCanceledListener` tìm `GoogleSubscription` bằng `purchase_token` → tìm được (bảng không soft-delete).
- Gọi `SubscriptionService::cancel()` → `updateSubscription()` → **BUG**: `$subscription->user` là null (user soft-deleted).
- **Fix**: dùng `$subscription->user()->withTrashed()->first()` và null-safe update.

## Acceptance criteria

- [ ] `DELETE /auth/me` với subscription Google active → Google API cancel được gọi, user bị xóa, không xóa DB ngay.
- [ ] Webhook `SubscriptionCanceled` đến sau → DB subscription được update, không crash dù user soft-deleted.
- [ ] `DELETE /auth/me` với subscription Apple → bỏ qua provider cancel, user vẫn bị xóa.
- [ ] Không có subscription → user bị xóa bình thường.
- [ ] Google cancel API thất bại → 500, user không bị xóa, JWT vẫn hợp lệ.

## Quyết định (chốt qua ASK)

- **Google cancel type**: `purchases.subscriptions.cancel` (dừng auto-renew, không revoke ngay).
- **Apple**: bỏ qua outbound cancel; chờ user hủy tay qua App Store.
- **Error handling**: fail_abort — 500 nếu Google API fail, không xóa account.
- **Location**: method `cancelSubscription()` trong `GoogleService` hiện có.
- **DB update**: KHÔNG update DB trực tiếp — để webhook xử lý.
- **Order**: cancel provider → invalidate JWT → soft-delete (tránh JWT bị invalidate khi lỗi).

---

## Update 2026-05-29 — SubscriptionManager (refactor)

### Ghi chú gốc
- cần làm 1 subscription manager để có code của mấy action kiểu cancel gói, chứ không gọi trực tiếp GoogleService vào ProfileController; manager sẽ nhận thông tin và check xem user đang dùng provider nào rồi quyết định gọi service đó

### Thay đổi
- Tạo `App\Share\Services\Subscription\SubscriptionManager` (trong cùng folder `app/Share/Services/Subscription/`).
- Manager inject `GoogleService` (và `AppleService` cho tương lai), expose method `cancelProvider(Subscription $subscription): void`.
- `ProfileController` đổi inject `GoogleService` → `SubscriptionManager`.
- `GoogleService::cancelSubscription()` giữ nguyên (internal).

### Quyết định
- **Tên class**: `SubscriptionManager`.
- **Method**: `cancelProvider(Subscription $subscription): void`.
- **Provider routing**: switch theo `$subscription->provider`; Google → gọi `GoogleService::cancelSubscription()`; Apple → log skip; unknown → log warning.
- **Location**: `app/Share/Services/Subscription/SubscriptionManager.php`.

## Liên quan

- `app/Share/Services/Subscription/GoogleService.php`
- `app/Share/Services/Subscription/SubscriptionService.php`
- `app/Web/Http/Controllers/API/V1/Auth/ProfileController.php`
- `docs/specs/web/delete-auth/spec.md`
- Imdhemy: `vendor/imdhemy/laravel-purchases/src/Subscription.php` (`cancel()` method)
