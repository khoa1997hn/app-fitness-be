# Enum

## Package & vị trí

- Package: **`bensampo/laravel-enum`** (`BenSampo\Enum\Enum`).
- Vị trí: `app/Share/Enums/`.
- Base class chung: `app/Share/Enums/Enum.php` (extends `BenSampo\Enum\Enum`). MỌI enum mới đều extends base này, KHÔNG extend `BenSampoEnum` trực tiếp.

## Đặt tên

- Class name: PascalCase, danh từ rõ nghĩa, KHÔNG suffix `Enum` (ví dụ `Plan`, `SubscriptionStatus` — KHÔNG `PlanEnum`).
- Constant: PascalCase (`Active`, `Cancelled`, `InGracePeriod`) — quy ước của BenSampo.
- Value: snake_case (`active`, `in_grace_period`).

```php
<?php

namespace App\Share\Enums;

class SubscriptionStatus extends Enum
{
    public const Active = 'active';
    public const Expired = 'expired';
    public const InGracePeriod = 'in_grace_period';
}
```

## Trong Model

### 1. BẮT BUỘC cast

Khai báo trong method `casts()`:

```php
protected function casts(): array
{
    return [
        'plan'   => Plan::class,
        'status' => SubscriptionStatus::class,
    ];
}
```

Không cast → `$model->plan` trả raw string → mất type safety + IDE không hint được.

### 2. BẮT BUỘC PHPDoc

Trong block PHPDoc của class Model phải khai báo từng field enum:

```php
/**
 * @property Plan $plan
 * @property SubscriptionStatus $status
 */
class Subscription extends Model { ... }
```

Đầy đủ giúp:
- IDE auto-complete (PhpStorm/VS Code).
- PHPStan static analysis pass.
- Dev đọc biết kiểu mong đợi.

### 3. Import class enum trong Model

```php
use App\Share\Enums\Plan;
use App\Share\Enums\SubscriptionStatus;
```

## Trong Response (API Web)

Khi map field response, **KHÔNG cần `->value`**.

### ❌ Sai (rườm rà)
```php
'plan'   => $subscription->plan->value,
'status' => $subscription->status->value,
```

### ✅ Đúng
```php
'plan'   => $subscription->plan,
'status' => $subscription->status,
```

**Vì sao**: `BenSampo\Enum\Enum` implement `__toString()` trả về `$this->value`. Khi Laravel serialize JSON, instance enum tự convert thành raw value.

### Khi nào VẪN cần `->value`

- So sánh trong điều kiện: `if ($status->value === 'active')` — nhưng ưu tiên `$status->is(SubscriptionStatus::Active)`.
- Đẩy vào method/SDK của bên thứ 3 không hiểu instance Enum.

## Trong Controller / Service

### So sánh
```php
// ❌ Tránh
if ($sub->status->value === 'active') { ... }

// ✅ Khuyến nghị
if ($sub->status->is(SubscriptionStatus::Active)) { ... }
```

### Method signature

Theo `docs/rules/02-code-quality.md`: type-hint là `string`, KHÔNG type-hint class enum (để linh hoạt). Truyền vẫn dùng `EnumClass::Value`:

```php
public function setStatus(string $status): void { ... }

$svc->setStatus(SubscriptionStatus::Active);  // gọi
```

## Trong Swagger annotation

Liệt kê value (raw string) qua `enum:` field:

```php
new OA\Property(
    property: 'status',
    type: 'string',
    enum: ['active', 'expired', 'in_grace_period'],
    example: 'active'
)
```

KHÔNG ghi PascalCase constant — Swagger phải là raw value để khớp JSON.

## Khi thêm enum mới — checklist

- [ ] File ở `app/Share/Enums/<Name>.php`?
- [ ] Extends base `Enum` (KHÔNG extends `BenSampoEnum` trực tiếp)?
- [ ] Constant PascalCase, value snake_case?
- [ ] Có field nào trong Model dùng enum này → cast + PHPDoc đầy đủ?
- [ ] Có endpoint Web response trả enum → Swagger annotation liệt kê value?
- [ ] Có method signature dùng enum → type-hint `string`?
