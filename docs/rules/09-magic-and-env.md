# Magic value & env / config conventions

## 1. Không dùng magic text / magic number

Cấm string/số hardcode lặp lại hoặc có ý nghĩa nghiệp vụ trong code logic.

### ❌ Sai
```php
if ($user->status === 'active' && $sub->plan === 'plus') { ... }
$qty = 10;
$retry = 3;
```

### ✅ Đúng
- String/số là **giá trị enum nghiệp vụ** → tạo Enum trong `app/Share/Enums/`:
  ```php
  if ($user->status === UserStatus::Active && $sub->plan === SubscriptionPlan::Plus) { ... }
  ```
- Số/string là **hằng cấu hình** (limit, timeout, retry, batch size…) → đưa qua env + config:
  ```php
  $qty = config('shop.cart.max_quantity');
  $retry = config('queue.retry_count');
  ```
- String/số chỉ dùng 1 chỗ và đã rõ nghĩa qua context → có thể giữ inline (không over-extract).

### Khi nào extract

Áp dụng quy tắc "rule of three":
- Dùng ≥ 2 chỗ → BẮT BUỘC extract (constant / enum / config).
- Dùng 1 chỗ + giá trị nghiệp vụ (status, plan, type) → BẮT BUỘC enum.
- Dùng 1 chỗ + giá trị kỹ thuật (limit, timeout) → cân nhắc, nếu có khả năng đổi theo môi trường → đưa qua config.

## 2. ENV phải qua CONFIG

**CẤM** gọi `env(...)` trực tiếp trong code logic (Controller, Service, Job, Listener, Model).

`env()` chỉ được dùng trong file `config/*.php`. Code logic gọi qua `config(...)`.

### Vì sao
- `php artisan config:cache` (production) sẽ làm `env()` trong code trả `null`.
- Tập trung default + validation tại 1 nơi.
- Test dễ override qua `config(['key' => 'value'])`.

### ❌ Sai
```php
$bucket = env('AWS_BUCKET');
```

### ✅ Đúng
```php
// config/filesystems.php
'aws_bucket' => env('AWS_BUCKET'),

// Code logic
$bucket = config('filesystems.aws_bucket');
```

## 3. Đặt tên ENV

Mọi env key BẮT BUỘC có **prefix** theo provider hoặc theo feature module.

### Mẫu

| Prefix | Dùng cho | Ví dụ |
|---|---|---|
| `APP_` | Laravel core | `APP_NAME`, `APP_ENV` |
| `DB_` | Database connection | `DB_HOST`, `DB_PORT` |
| `REDIS_` | Redis | `REDIS_HOST` |
| `MAIL_` | Mail | `MAIL_HOST` |
| `AWS_` | AWS provider | `AWS_BUCKET`, `AWS_ACCESS_KEY_ID` |
| `GOOGLE_` | Google service | `GOOGLE_PLAY_PACKAGE_NAME` |
| `APPSTORE_` | Apple App Store | `APPSTORE_ISSUER_ID` |
| `JWT_` | JWT auth | `JWT_SECRET` |
| `L5_SWAGGER_` | L5 Swagger | `L5_SWAGGER_GENERATE_ALWAYS` |
| `PLAN_` | Module Subscription Plan | `PLAN_BASIC_PRICE` |
| `PAYMENT_` | Module Payment | `PAYMENT_CURRENCY` |

### Quy tắc
- KHÔNG dùng tên mơ hồ kiểu `KEY`, `SECRET`, `TOKEN`, `URL` đứng riêng.
- Nested theo module: `<MODULE>_<SUBKEY>_<DETAIL>` (ví dụ `PLAN_BASIC_GOOGLE_ITEM_ID`).
- UPPER_SNAKE_CASE, không gạch nối, không lowercase.

## 4. Khi thêm ENV mới

1. **Thêm vào CẢ `.env` lẫn `.env.example`**. Lệch key → fail review.
2. `.env.example` ghi value mẫu hoặc rỗng (KHÔNG ghi value thật).
3. `.env` ghi value local thực tế (KHÔNG commit nếu chứa secret production).
4. Thêm mapping vào file config tương ứng. Nếu chưa có file config phù hợp → **tạo mới**.

## 5. Tổ chức file CONFIG

Khi đưa env vào config, BẮT BUỘC chia file theo **provider** hoặc **module**.

### Quy tắc
- 1 provider/module = 1 file config riêng.
- Tên file kebab-case theo provider: `aws.php`, `google-play.php`, `appstore.php`.
- Hoặc theo module nghiệp vụ: `subscription.php`, `payment.php`, `cart.php`.
- KHÔNG dồn nhiều provider vào `services.php` mặc định của Laravel — services.php chỉ giữ những gì Laravel core dùng (mail, stripe…).
- Đã có sẵn: `config/jwt.php`, `config/liap.php` (IAP), `config/translatable.php`, `config/app_file.php`, `config/app_payment.php` — tham chiếu pattern này.

### Mẫu file config mới

```php
// config/aws.php
return [
    'access_key_id'     => env('AWS_ACCESS_KEY_ID'),
    'secret_access_key' => env('AWS_SECRET_ACCESS_KEY'),
    'region'            => env('AWS_DEFAULT_REGION', 'ap-southeast-1'),
    'bucket'            => env('AWS_BUCKET'),
];
```

Code logic gọi: `config('aws.bucket')`.

## 6. Checklist khi thêm 1 env mới

- [ ] Tên có prefix provider/module rõ ràng?
- [ ] Đã thêm vào `.env`?
- [ ] Đã thêm vào `.env.example` (với value mẫu hoặc rỗng)?
- [ ] Đã map vào file config phù hợp (tạo file mới nếu cần)?
- [ ] Code logic chỉ gọi `config(...)`, không gọi `env(...)`?
- [ ] Nếu env có default value → default đặt ở config, không ở code logic?
