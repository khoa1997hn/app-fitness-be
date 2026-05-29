# openapi-writer

> **HIGH RULE**: KHÔNG BỊA response/field. Annotation phải khớp 100% code thực tế. (Xem `docs/rules/00-core.md`)

## Mục tiêu

Viết / cập nhật OpenAPI annotation cho endpoint Web V1 sau khi implementer code xong. Đảm bảo annotation khớp với mapping field thực tế trong controller.

## Khi nào chạy

- Trong workflow `/implement-spec`, `/update-spec`: SAU `implementer`, TRƯỚC `reviewer-rules`.
- Trong workflow `/fix-bug`: CHỈ chạy nếu diff chạm `app/Web/Http/Controllers/API/V1/**`.
- Skip nếu diff không chạm endpoint Web V1.

## Input

- Diff của implementer (đặc biệt các file trong `app/Web/Http/Controllers/API/V1/`).

## Output

- Annotation `#[OA\...]` đã thêm/sửa trong controller.
- File `storage/api-docs/api-docs.json` được regenerate qua `php artisan l5-swagger:generate`.
- Tick `[x]` trong task.md (pha OpenAPI).

## Tài liệu cần đọc

- `docs/rules/08-swagger.md`
- `docs/rules/04-api-response.md` (để biết format response controller đang map)
- 1 file controller mẫu đã có annotation đầy đủ (gợi ý: `app/Web/Http/Controllers/API/V1/BannerController.php`)
- `app/Web/Http/Controllers/API/V1/APIController.php` (base — đã định nghĩa `OA\Info`, `OA\Server`, `OA\SecurityScheme`)

## Stack thực tế

- Package: `darkaonline/l5-swagger` v10.
- Annotation style: **PHP 8 Attributes** (`use OpenApi\Attributes as OA;` rồi `#[OA\Get(...)]`).
- **KHÔNG** dùng docblock kiểu `/** @OA\Get(...) */`.
- Output: `storage/api-docs/api-docs.json`.

## Quy trình

1. Lấy diff của implementer. Lọc file trong `app/Web/Http/Controllers/API/V1/`.
2. Với mỗi endpoint mới/sửa:
   - Đọc lại mapping field trong controller (phần `ResponseAPI::success([...])`).
   - Viết/sửa attribute `#[OA\Get|Post|Put|Delete|Patch(...)]` lên method.
   - Bắt buộc gồm: `path`, `summary`, `description`, `tags`, `parameters` (nếu có), `requestBody` (nếu có), `responses` (nested đầy đủ field theo mapping).
   - Endpoint cần auth → thêm `security: [['bearerAuth' => []]]`.
   - Status codes: liệt kê đủ (200/201/204 success, 400/401/403/404/422/500 lỗi).
3. So sánh từng property trong `OA\Response` với mapping field thực tế:
   - Field trong code mà thiếu trong annotation → thêm.
   - Field trong annotation mà code không trả → xóa khỏi annotation.
   - Type/example phải khớp.
4. Regenerate docs JSON:
   ```bash
   sail exec --user sail laravel.test php artisan l5-swagger:generate
   ```
5. Nếu có warning → fix annotation rồi generate lại.
6. Tick task.md pha OpenAPI.

## Mẫu attribute (tham khảo BannerController)

```php
#[OA\Get(
    path: '/banners',
    summary: 'Lấy danh sách banners',
    description: 'Mô tả chi tiết...',
    tags: ['Banners'],
    parameters: [
        new OA\Parameter(name: 'id', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Thành công',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string'),
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(/* ... */)),
                ]
            )
        ),
    ]
)]
public function index(Request $request): JsonResponse { ... }
```

## Cấm

- CẤM viết annotation kiểu docblock `/** @OA\... */` (project dùng attributes).
- CẤM bịa field trong response — phải đối chiếu mapping thực tế.
- CẤM bỏ status code lỗi (401/422/500…) khi endpoint thực sự có thể trả.
- CẤM bỏ qua `l5-swagger:generate` — không generate thì JSON không cập nhật.
