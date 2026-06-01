# app/ — kiến trúc & code quality

Đụng bất kỳ file PHP nào trong `app/` — áp dụng cả 3:

## Kiến trúc (rule 01)
- Logic CRUD/business → Controller. Service chỉ khi dùng lại ≥ 2 chỗ HOẶC > ~30 dòng có nhánh.
- **KHÔNG dùng Repository pattern**. Eloquent gọi trực tiếp.
- Không tự tạo Action/UseCase/Manager layer.

## Code quality (rule 02)
- Eloquent: luôn `Model::query()->...`.
- Catch: `catch (\Throwable $e)`, KHÔNG `\Exception`.
- Throw: dùng domain exception trong `app/Share/Exceptions/<Domain>/`, KHÔNG `\Exception` raw.
- CẤM `ValidationException` trong Service — validation thuộc FormRequest.
- Enum signature: type-hint `string`, gọi truyền `EnumClass::Value`.
- Migration/Model/Controller: BẮT BUỘC `php artisan make:...` qua Sail.

## Cấu trúc (rule 03)
- `app/` chỉ có 3 folder: `Admin`, `Web`, `Share`.
- `Share/` là folder CORE — chứa MỌI code dùng chung. Models BẮT BUỘC ở `app/Share/Models/`.
- Tách vào Web/Admin chỉ khi thực sự chỉ 1 module dùng (controller/request/view).

## Magic value & env (rule 09)
- CẤM gọi `env(...)` trong code logic — đi qua `config(...)`.
- Magic text/số có nghĩa nghiệp vụ → Enum; limit/retry/timeout → config.

Chi tiết: [`docs/rules/01-architecture.md`](../docs/rules/01-architecture.md), [`02-code-quality.md`](../docs/rules/02-code-quality.md), [`03-project-structure.md`](../docs/rules/03-project-structure.md), [`09-magic-and-env.md`](../docs/rules/09-magic-and-env.md).
