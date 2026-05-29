# Code quality

## Eloquent

- Luôn gọi `Model::query()->...` để IDE auto-complete.
  - Ví dụ: `User::query()->where(...)->get()` (KHÔNG `User::where()->get()`).
  - Ví dụ: `Admin::query()->firstOrCreate([...])`.

## Exception

- **Catch**: dùng `catch (\Throwable $e)`, KHÔNG `catch (\Exception $e)`.
- **Throw**: tạo domain exception trong `app/Share/Exceptions/<Domain>/`, kế thừa base phù hợp (ví dụ `SubscriptionException`). KHÔNG `throw new \Exception(...)` ad-hoc.

## Enum trong method signature

- Type-hint là `string`, KHÔNG type-hint enum class (để linh hoạt).
- Khi gọi method vẫn truyền enum trực tiếp:
  ```php
  public function setStatus(string $status): void { ... }
  $svc->setStatus(SubscriptionStatus::Active);
  ```

## Format

- Tuân theo Laravel Pint default. Sau khi xong task PHẢI chạy pint (xem `docs/guides/pint-format.md`).

## Không tự code thay vì artisan

- Migration: BẮT BUỘC `php artisan make:migration ...`, không tự tạo file.
- Model/Controller/Request: BẮT BUỘC artisan commands.
