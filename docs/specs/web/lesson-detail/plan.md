# Plan: API chi tiết Lesson

> Kế hoạch triển khai dựa trên `spec.md` cùng folder.

## Tóm tắt

Thêm `LessonController` (Web V1) với action `show` trả thông tin 1 lesson — tái dùng logic map giống `ProgramController::mapLesson()`. Đăng ký route `GET /api/v1/lessons/{lesson}` trong nhóm `auth:api`. Không migration, không service mới.

## Phụ thuộc

- Migration mới: không
- Model mới: không
- Endpoint mới: `GET /api/v1/lessons/{lesson}`
- Endpoint sửa: không
- View Admin mới/sửa: không
- Service mới: không (dùng lại `VideoWatchProgressService` inject)
- Package/composer mới: không

## Phân tích ảnh hưởng

### Migration
- Không

### Model
- Không thay đổi

### Endpoint
- `GET /api/v1/lessons/{lesson}` — mới — auth JWT, response lesson detail (10 field)

### Route file
- `routes/api.php` — thêm route sau block `lessons/favorites`

### Swagger
- `LessonController` — `#[OA\Get]` đầy đủ field

## Các pha

### Pha 1 — Controller + Route
- Mục tiêu: Tạo `LessonController::show`, đăng ký route
- Files: `app/Web/Http/Controllers/API/V1/LessonController.php`, `routes/api.php`

### Pha 2 — OpenAPI
- Mục tiêu: Swagger attribute + regenerate api-docs
- Files: `LessonController.php`, `storage/api-docs/api-docs.json`

## Rủi ro

- Route `lessons/{lesson}` có thể conflict với `lessons/favorites` nếu đặt sai thứ tự → đặt sau route static `lessons/favorites`.
- Duplicate logic `mapLesson` với `ProgramController` → reviewer-duplicate xử lý nếu cần extract.

## Verify thủ công

- Không token → `GET /api/v1/lessons/1` → 401
- Token hợp lệ + lesson tồn tại → 200, đủ 10 field, không có `file`/video url
- Lesson id không tồn tại → 404
- `is_favorited` đúng sau favorite/unfavorite
- `progress` đúng sau watch-progress
