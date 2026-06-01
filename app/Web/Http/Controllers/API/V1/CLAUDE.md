# Web API V1 — response + Swagger

## Response (rule 04)
- CẤM return thẳng object/Collection. Phải map từng field vào array.
- Helper: `ResponseAPI::success()` / `ResponseAPI::error()` — cấu trúc `{ success, message, data, errors? }`.
- CẤM `response()->json()` trực tiếp — luôn qua `ResponseAPI`.
- Auth: endpoint đã `auth:api` → `auth()->user()`. CẤM `auth('api')->user()` (chỉ login/logout/refresh dùng `auth('api')`).
- Field enum trong response: dùng `$model->field` (KHÔNG `->value`).

## Swagger (rule 08)
- Style: **PHP 8 Attributes** (`use OpenApi\Attributes as OA;` + `#[OA\Get(...)]`). KHÔNG docblock.
- Mỗi endpoint: `path` (không kèm `/api/v1`), `summary`, `description`, `tags`, `parameters`, `requestBody`, `responses` nested ĐẦY ĐỦ field, status codes (200, 401, 403, 404, 422, 500).
- `security: [['bearerAuth' => []]]` cho endpoint cần auth.
- **CẤM schema mơ hồ**: `type: 'object'` không có `properties`. File field BẮT BUỘC `{ path, name, extension, size, url }`.
- Sau khi sửa attribute → `sail exec --user sail laravel.test php artisan l5-swagger:generate`.

## Đa ngôn ngữ (rule 14)
- Header `x-locale` (vi/en). Response field translated lấy theo current locale tự động qua Astrotomic.

Chi tiết: [`docs/rules/04-api-response.md`](../../../../../../docs/rules/04-api-response.md), [`08-swagger.md`](../../../../../../docs/rules/08-swagger.md).
