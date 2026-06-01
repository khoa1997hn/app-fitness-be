# Subscription & IAP

## Service split
- `SubscriptionService` — logic CHUNG: activate / renew / cancel / expire / refund.
- `GoogleService`, `AppleService` — logic RIÊNG provider: verify token/receipt, parse plan từ config, tạo/update record per-provider.
- `TrialService` — start trial.
- Provider mới (Stripe...) → service mới cùng pattern, KHÔNG ghép vào Google/Apple.

## Bắt buộc khi viết code subscription
- **DB::transaction() + lockForUpdate()** mọi thao tác `subscriptions` table (webhook concurrent với client).
- Update **đồng thời** `users.subscription_status` + `users.plan` mỗi lần đổi subscription.
- **Log channel riêng**: `Log::channel('subscription')` với context `user_id` + provider identifier (`purchase_token` / `receipt_id`).
- **Domain exception**: `app/Share/Exceptions/Subscription/` (`SubscriptionException` base). CẤM throw `\Exception` raw.
- **Plan price + product ID**: `config('app_payment.plans.<key>')`. CẤM hardcode.

## Cấm
- CẤM gọi imdhemy SDK trực tiếp từ Controller — phải qua Google/Apple service.
- CẤM update `subscriptions` từ controller — qua `SubscriptionService`.

## Listener
- Mỗi event provider = 1 file extends `BaseGoogleListener` / `BaseAppleListener`.
- Register trong `EventServiceProvider`.

Chi tiết: [`docs/rules/13-subscription-iap.md`](../../../../docs/rules/13-subscription-iap.md).
