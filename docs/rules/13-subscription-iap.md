# Subscription & In-App Purchase

## Stack

- Package: `imdhemy/laravel-purchases` — handle webhook + verify receipt/token Apple & Google.
- Services: `app/Share/Services/Subscription/`
  - `SubscriptionService` — logic CHUNG (activate/renew/cancel/expire/refund).
  - `GoogleService` — verify purchase token Google Play.
  - `AppleService` — verify receipt Apple App Store.
  - `TrialService` — start trial.
- Listeners: `app/Share/Listeners/Subscriptions/{Google, Apple}/` — handle event lifecycle qua webhook.
- Exceptions: `app/Share/Exceptions/Subscription/` (`SubscriptionException` base, `SubscriptionNotFoundException`, `InvalidReceiptException`).
- Log channel: `Log::channel('subscription')` — TÁCH RIÊNG khỏi default channel để dễ trace.
- Config: `config/app_payment.php` (plan price + provider item ID), `config/liap.php` (imdhemy routing).
- Tables: `subscriptions` (master) + `google_subscriptions`, `apple_subscriptions` (per-provider raw data) + cột `subscription_status` / `plan` trong `users`.

## Quy ước viết code subscription

### 1. Logic chung đặt trong `SubscriptionService`

Mọi thao tác lifecycle chung (activate / renew / cancel / expire / refund) → method trong `SubscriptionService`. KHÔNG viết lại trong từng provider service.

### 2. Provider-specific logic đặt trong service riêng

`GoogleService` / `AppleService` chỉ chứa:
- Verify receipt/token qua imdhemy SDK.
- Parse plan từ `item_id` (Google) / `product_id` (Apple) qua `config('app_payment.plans')`.
- Tạo / update record per-provider (`GoogleSubscription` / `AppleSubscription`).
- Gọi `SubscriptionService::activate()` hoặc `TrialService::startTrial()` cho phần chung.

Khi thêm provider mới (ví dụ Stripe) → tạo `StripeService` cùng pattern, KHÔNG ghép vào Google/Apple service.

### 3. BẮT BUỘC dùng DB transaction + `lockForUpdate`

Mọi thao tác đụng `subscriptions` table phải bọc trong `DB::transaction()` và lock row:

```php
return DB::transaction(function () use (...) {
    Subscription::query()
        ->where('user_id', $user->id)
        ->lockForUpdate()
        ->first();

    // ... thao tác update/create
});
```

Lý do: webhook IAP có thể chạy concurrent với purchase verification từ client.

### 4. BẮT BUỘC update đồng thời

Mỗi lần đổi subscription PHẢI update đồng bộ:
- `subscriptions` row (status, expires_at, plan, ...).
- `users.subscription_status` + `users.plan`.

→ Đặt logic update user trong cùng service method, KHÔNG để controller tự update.

### 5. BẮT BUỘC log subscription event

Mỗi method service log qua channel riêng `subscription`:

```php
protected LoggerInterface $logger;

public function __construct(...) {
    $this->logger = Log::channel('subscription');
}

// trong logic
$this->logger->info('[Google] Verify purchase', [...]);
$this->logger->error('[Google] Error during purchase verification', [
    'error' => $e->getMessage(),
    'user_id' => $user->id,
    // ...
]);
```

Log phải có context đủ để trace: `user_id`, `purchase_token` / `receipt_id`, `item_id`, `error`.

### 6. Exception

- Throw `SubscriptionException` (hoặc subclass: `SubscriptionNotFoundException`, `InvalidReceiptException`).
- KHÔNG throw `\Exception` / `\RuntimeException` raw (xem `docs/rules/02-code-quality.md`).
- Catch ở controller → trả `ResponseAPI::error()` với message rõ ràng cho client.

### 7. Mapping plan → provider product

`config/app_payment.php`:
```php
'plans' => [
    'basic' => [
        'price'             => env('PLAN_BASIC_PRICE'),
        'google_item_id'    => env('PLAN_BASIC_GOOGLE_ITEM_ID'),
        'apple_product_id'  => env('PLAN_BASIC_APPLE_PRODUCT_ID'),
    ],
    // ...
],
```

Khi thêm plan mới → thêm cả 3 env per provider + thêm vào `Plan` enum (`app/Share/Enums/Plan.php`).

### 8. Listener webhook

Mỗi event provider → 1 listener riêng:
- Google: `Purchased`, `Renewed`, `Canceled`, `Expired`, `GracePeriod`, `Revoked`, ...
- Apple: `InitialBuy`, `DidRenew`, `DidFailToRenew`, `Cancel`, `Expired`, `GracePeriodExpired`, `Refund`, ...

Tất cả extends `BaseGoogleListener` / `BaseAppleListener` để share logic chung (parse payload, find user, log).

Khi thêm event mới → tạo listener mới + register trong `EventServiceProvider`. KHÔNG ghép nhiều event vào 1 listener.

## Cấm

- CẤM gọi imdhemy SDK trực tiếp từ Controller — phải qua `GoogleService` / `AppleService`.
- CẤM update bảng `subscriptions` từ controller — phải qua `SubscriptionService`.
- CẤM thiếu DB transaction khi đụng `subscriptions`.
- CẤM update user mà quên đồng bộ `subscription_status` + `plan`.
- CẤM dùng default log channel cho subscription event — phải `Log::channel('subscription')`.
- CẤM hardcode plan price / product ID — luôn qua `config('app_payment.*')`.
- CẤM throw raw `\Exception` / `\RuntimeException` — phải qua domain exception trong `app/Share/Exceptions/Subscription/`.
