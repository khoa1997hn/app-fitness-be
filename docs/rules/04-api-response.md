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
        'plan'   => $subscription->plan,       // enum cast — không cần ->value
        'status' => $subscription->status,     // enum cast — không cần ->value
        'expires_at' => $subscription->expires_at?->toIso8601String(),
    ],
]);
```

> Enum field KHÔNG cần `->value` — BenSampo Enum tự convert qua `__toString()` khi JSON serialize. Xem `docs/rules/11-enum.md`.

## Format chuẩn

- Helper: `ResponseAPI::success()`, `ResponseAPI::error()`.
- Cấu trúc: `{ success: bool, message: string, data: mixed, errors?: array }`.
- Base controller: `app/Web/Http/Controllers/API/V1/APIController.php`.

## Bắt buộc dùng ResponseAPI

- **Web API** (`app/Web/Http/Controllers/API/V1/*`): CẤM `return response()->json([...])` trực tiếp — luôn `ResponseAPI::success()` / `ResponseAPI::error()`.
- **Admin JSON** (ajax upload, v.v.): Cùng quy tắc — dùng `ResponseAPI` hoặc trait/concern gọi `ResponseAPI` (vd. `HandlesPresignedFileUpload`).
- Object implement `JsonSerializable` (vd. `File`) có thể nằm trong `data` — Laravel serialize qua `jsonSerialize()`.

## Vì sao

- Tránh leak field nhạy cảm.
- Stable contract khi model đổi.
- Khớp với Swagger docs (xem `docs/rules/08-swagger.md`).
