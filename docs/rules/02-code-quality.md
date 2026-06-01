# Code quality

## Eloquent

- Luôn gọi `Model::query()->...` để IDE auto-complete.
  - Ví dụ: `User::query()->where(...)->get()` (KHÔNG `User::where()->get()`).
  - Ví dụ: `Admin::query()->firstOrCreate([...])`.

### KHÔNG truy cập table trực tiếp — quy hết về Model

- **CẤM `DB::table('...')`, `DB::select(...)`, `DB::insert/update/delete/statement` với tên bảng hard-code.** Mọi truy vấn dữ liệu PHẢI đi qua Eloquent Model + quan hệ:
  - Quan hệ: `belongsTo` / `hasMany` / `belongsToMany` (tên bảng pivot dạng `'lesson_favorites'` trong relationship là HỢP LỆ — đó là Laravel chuẩn, không phải "truy cập table trực tiếp").
  - Aggregate: `withSum` / `withCount` / `withAvg` / `whereHas` thay cho `join` thủ công.
  - `selectRaw('SUM(...)')` cho phép tính tổng/đếm trên query của Model — KHÔNG phải tên bảng nên KHÔNG vi phạm rule.
- **Ngoại lệ duy nhất: query quá phức tạp** mà Eloquent diễn đạt được nhưng kém rõ ràng/kém hiệu năng rõ rệt (vd. window function, CTE nhiều tầng). Khi đó:
  - Vẫn ưu tiên bắt đầu từ `Model::query()` rồi mới `selectRaw`/subquery.
  - Nếu buộc dùng raw SQL → thêm comment giải thích VÌ SAO không dùng Eloquent được.
- `DB::transaction(...)` KHÔNG thuộc rule này (không truy cập table) — dùng thoải mái.

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
