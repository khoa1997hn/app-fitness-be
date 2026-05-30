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

## Không được define schema mơ hồ

**CẤM** dùng `type: 'object'` hoặc `type: 'array'` mà không khai báo đầy đủ properties/items:

```php
// ❌ SAI — mơ hồ
new OA\Property(property: 'thumbnail', type: 'object', nullable: true),
new OA\Property(property: 'data', type: 'object'),

// ✅ ĐÚNG — khai báo đầy đủ
new OA\Property(
    property: 'thumbnail',
    properties: [
        new OA\Property(property: 'path', type: 'string', example: 'lessons/thumbnails/img.jpg'),
        new OA\Property(property: 'name', type: 'string', example: 'img.jpg'),
        new OA\Property(property: 'extension', type: 'string', example: 'jpg', nullable: true),
        new OA\Property(property: 'size', type: 'integer', example: 102400, nullable: true),
        new OA\Property(property: 'url', type: 'string', example: 'http://localhost/storage/...'),
    ],
    type: 'object',
    nullable: true
),
```

**Quy tắc cụ thể:**
- File/media object (cover, thumbnail, image, file, ...): luôn khai báo đủ `{ path, name, extension, size, url }`.
- Nested object trong response: khai báo tất cả các fields thực tế trả về.
- Nếu response của endpoint A "cùng shape" với endpoint B: chép lại schema đầy đủ, không dùng `description: 'Cùng shape ...'` rồi để `type: 'object'`.
- Exception duy nhất: `data: null` (endpoint trả null) — dùng `type: 'object', nullable: true, example: null`.

## Mẫu file schema chuẩn

```php
new OA\Property(
    property: 'cover',   // hoặc 'thumbnail', 'image', 'file', ...
    description: 'Ảnh cover (theo locale)',
    properties: [
        new OA\Property(property: 'path', description: 'Đường dẫn file', type: 'string', example: 'path/to/file.jpg'),
        new OA\Property(property: 'name', description: 'Tên file', type: 'string', example: 'file.jpg'),
        new OA\Property(property: 'extension', description: 'Phần mở rộng file', type: 'string', example: 'jpg', nullable: true),
        new OA\Property(property: 'size', description: 'Kích thước file (bytes)', type: 'integer', example: 102400, nullable: true),
        new OA\Property(property: 'url', description: 'URL đầy đủ', type: 'string', example: 'http://localhost/storage/path/to/file.jpg'),
    ],
    type: 'object',
    nullable: true
),
```

## Mẫu

Xem `app/Web/Http/Controllers/API/V1/BannerController.php` để tham khảo pattern.

## Agent phụ trách

Việc viết/cập nhật annotation là vai trò của `docs/agents/openapi-writer.md`.
