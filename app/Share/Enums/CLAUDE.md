# Enums

## Quy ước
- Package: `bensampo/laravel-enum`. Mọi enum extends base `App\Share\Enums\Enum` (KHÔNG extends `BenSampo\Enum\Enum` trực tiếp).
- Tên class: PascalCase, **KHÔNG suffix `Enum`** (`Plan`, không `PlanEnum`).
- Const: PascalCase (`Active`, `InGracePeriod`). Value: snake_case (`active`, `in_grace_period`).

## Trong Model
- BẮT BUỘC cast trong `casts()`: `'plan' => Plan::class`.
- BẮT BUỘC PHPDoc `@property Plan $plan` để IDE + PHPStan hint chuẩn.

## Trong Response
- **`$model->field`** — KHÔNG `->value` (BenSampo tự `__toString()`).

## So sánh
- Ưu tiên `$enum->is(EnumClass::Value)` thay vì `->value === 'x'`.

Chi tiết: [`docs/rules/11-enum.md`](../../../docs/rules/11-enum.md).
