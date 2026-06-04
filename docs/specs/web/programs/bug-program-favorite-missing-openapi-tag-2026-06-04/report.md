# Bug report: Thiếu OpenAPI tag — ProgramFavoriteController

## Mô tả

- **Endpoint**: `POST /api/v1/programs/favorites`
- **Triệu chứng**: Trong Swagger UI, endpoint không được gom nhóm tag (hiển thị mặc định / lẫn với endpoint khác).
- **Nguyên nhân**: Attribute `#[OA\Post(...)]` trong `ProgramFavoriteController` thiếu tham số `tags`.
- **Expected**: Endpoint có tag `Program Favorites` (đồng bộ pattern `LessonFavoriteController` dùng `Lesson Favorites`).
- **Actual**: Không khai báo `tags`.

## Phân loại

- **DỄ** — sửa 1 dòng annotation, regenerate OpenAPI.
- Mức độ: thấp
- Phạm vi: `POST /programs/favorites`, Swagger UI

## Nguyên nhân gốc

`ProgramFavoriteController::store` khai báo `#[OA\Post]` đủ path/summary/security nhưng thiếu `tags`, nên swagger-php không gán nhóm tag cho operation.

## Cách fix

Thêm `tags: ['Program Favorites']` vào `#[OA\Post(...)]` của method `store`, sau đó chạy `php artisan l5-swagger:generate`.

## Files đã sửa

- `app/Web/Http/Controllers/API/V1/ProgramFavoriteController.php` — thêm `tags: ['Program Favorites']`
- `storage/api-docs/api-docs.json` — regenerate (local, không commit nếu repo không track file này)

## Verify

- [x] `l5-swagger:generate` chạy thành công
- [x] `api-docs.json` chứa tag `Program Favorites` cho path `/programs/favorites`
- [x] `pint` pass trên `ProgramFavoriteController.php`
- [ ] Mở Swagger UI → endpoint `POST /programs/favorites` nằm nhóm **Program Favorites**
