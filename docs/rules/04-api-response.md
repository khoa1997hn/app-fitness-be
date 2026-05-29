# API response (Web)

## Không return full model

**CẤM** return thẳng object/Collection vào response. Phải map từng field cần thiết vào array.

Kể cả khi trả về tất cả field — vẫn phải liệt kê rõ từng phần tử.

### ❌ Sai
```php
return ResponseAPI::success(['subscription' => $subscription]);
```

### ✅ Đúng
```php
return ResponseAPI::success([
    'subscription' => [
        'id'     => $subscription->id,
        'plan'   => $subscription->plan->value,
        'status' => $subscription->status->value,
        'expires_at' => $subscription->expires_at?->toIso8601String(),
    ],
]);
```

## Format chuẩn

- Helper: `ResponseAPI::success()`, `ResponseAPI::error()`.
- Cấu trúc: `{ success: bool, message: string, data: mixed, errors?: array }`.
- Base controller: `app/Web/Http/Controllers/API/V1/APIController.php`.

## Vì sao

- Tránh leak field nhạy cảm.
- Stable contract khi model đổi.
- Khớp với Swagger docs (xem `docs/rules/08-swagger.md`).
