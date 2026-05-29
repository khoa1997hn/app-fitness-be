# Swagger / OpenAPI

Tất cả endpoint trong `app/Web/Http/Controllers/API/V1` **bắt buộc** có Swagger annotations đầy đủ.

## Stack thực tế

- Package: `darkaonline/l5-swagger` v10 (qua `zircote/swagger-php`).
- **Style annotation: PHP 8 Attributes**, KHÔNG dùng docblock `/** @OA\... */`.
- Import: `use OpenApi\Attributes as OA;` rồi dùng `#[OA\Get(...)]`, `#[OA\Response(...)]`, ...
- Base controller: `app/Web/Http/Controllers/API/V1/APIController.php` — đã định nghĩa `#[OA\Info]`, `#[OA\Server]`, `#[OA\SecurityScheme]`.
- Output JSON: `storage/api-docs/api-docs.json`.
- Command regenerate: `sail exec --user sail laravel.test php artisan l5-swagger:generate`.

## Yêu cầu chi tiết cho mỗi endpoint

1. **Method attribute** — `#[OA\Get|Post|Put|Patch|Delete]` với:
   - `path` (KHÔNG kèm prefix `/api/v1` — base đã có Server `/api/v1`).
   - `summary` — câu ngắn.
   - `description` — chi tiết, có thể nhiều dòng.
   - `tags` — array string, gom theo domain (`['Banners']`, `['Auth']`).
2. **`parameters`** (nếu có) — list `new OA\Parameter(name, in, required, schema)`.
3. **`requestBody`** (nếu có POST/PUT/PATCH với body) — nested đầy đủ field.
4. **`responses`** — nested ĐÚNG với mapping field thực tế trong controller (xem `docs/rules/04-api-response.md`):
   - 200/201/204 — success
   - 400 — bad request
   - 401 — chưa auth
   - 403 — không có quyền
   - 404 — không tìm thấy
   - 422 — validation fail
   - 500 — server error (nếu có khả năng)
5. **`security`** — endpoint cần auth thêm `security: [['bearerAuth' => []]]`.

## Đồng bộ với controller

- Khi đổi mapping field response trong controller → BẮT BUỘC đổi annotation cho khớp.
- Sau khi viết/sửa attribute → chạy `l5-swagger:generate` để cập nhật JSON.
- Vi phạm đồng bộ → fail review (xem `docs/guides/code-review-checklist.md`).

## Mẫu

Xem `app/Web/Http/Controllers/API/V1/BannerController.php` để tham khảo pattern.

## Agent phụ trách

Việc viết/cập nhật annotation là vai trò của `docs/agents/openapi-writer.md`.
