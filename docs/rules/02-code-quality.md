# Code quality

## Eloquent

- Luôn gọi `Model::query()->...` để IDE auto-complete.
  - Ví dụ: `User::query()->where(...)->get()` (KHÔNG `User::where()->get()`).
  - Ví dụ: `Admin::query()->firstOrCreate([...])`.

## Exception

- **Catch**: dùng `catch (\Throwable $e)`, KHÔNG `catch (\Exception $e)`.
- **Throw**: tạo domain exception trong `app/Share/Exceptions/<Domain>/`, kế thừa base phù hợp (ví dụ `SubscriptionException`). KHÔNG `throw new \Exception(...)` ad-hoc.
- **CẤM `ValidationException` trong Service** — validation input user thuộc FormRequest (Web) / Request validate (Admin). Service chỉ throw domain exception (vd. `InvalidFileInputException`) cho lỗi nghiệp vụ/config, không gắn field errors Laravel.

## Enum

- Package: `bensampo/laravel-enum`. Vị trí: `app/Share/Enums/`.
- Method signature: type-hint `string` (KHÔNG type-hint enum class), khi gọi vẫn truyền `EnumClass::Value`:
  ```php
  public function setStatus(string $status): void { ... }
  $svc->setStatus(SubscriptionStatus::Active);
  ```
- Model: BẮT BUỘC cast + PHPDoc `@property`.
- Response JSON: dùng `$model->field` (KHÔNG cần `->value`).

Chi tiết: [`docs/rules/11-enum.md`](11-enum.md).

## Format

- Tuân theo Laravel Pint default. Sau khi xong task PHẢI chạy pint (xem `docs/guides/pint-format.md`).

## Không tự code thay vì artisan

- Migration: BẮT BUỘC `php artisan make:migration ...`, không tự tạo file.
- Model/Controller/Request: BẮT BUỘC artisan commands.
