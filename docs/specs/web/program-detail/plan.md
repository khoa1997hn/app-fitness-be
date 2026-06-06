# Plan: API chi tiết Program

> Kế hoạch dựa trên `spec.md` cùng folder.

## Tóm tắt

Thêm `ProgramController@show` với route model binding, map `program` (tái dùng logic list) + `lessons` grouped theo type/level. Refactor map program chung cho index/show. Swagger + route.

## Phụ thuộc

- Migration: không
- Model: tái dùng Program, Lesson, Video
- Endpoint mới: `GET /api/v1/programs/{program}`
- Service: không

## Các pha

### Pha 1 — Controller
- `show(Program $program)` + private helpers `mapProgram`, `mapLesson`, `groupLessons`
- Refactor `index` dùng `mapProgram`

### Pha 2 — Route
- `routes/api.php`: `programs/{program}` + `auth:api`

### Pha 3 — OpenAPI
- `#[OA\Get]` cho show + generate

## Verify thủ công

- `GET /api/v1/programs/1` có token → 200, structure đúng
- `GET /api/v1/programs/99999` → 404
- Không có `file` trong lesson items

## Update 2026-05-29

### Tóm tắt
Flatten response `show`: spread field program + `lessons` trong `data`, bỏ key `program`.

### Verify
- `data.id`, `data.name`, … và `data.lessons` cùng cấp, không có `data.program`.

## Update 2026-05-29 (figma program detail)

### Tóm tắt
Bổ sung theo 3 ảnh figma: thêm lesson `thumbnail` (File jsonb + FileType::LessonThumbnail) và cột `day` (int) cho lessons; đổi sort nhóm sang `day` asc → `id` asc; trả `thumbnail` + `day` trong lesson item. Tiến độ xem video do FE lưu local.

### Phụ thuộc
- Migration mới: `add_day_to_lessons_table`, `add_thumbnail_to_lesson_translations_table`
- Model: Lesson (+ day, thumbnail), LessonTranslation (+ thumbnail FileCast)
- Enum: `FileType::LessonThumbnail` + entry `config/app_file.php`
- Controller: `mapLesson` thêm day/thumbnail; `sortLessons` theo day
- Seeder: set day + thumbnail

### Verify
- lesson item: `id, day, name, description, thumbnail, duration_seconds`; không có `file`
- Sort theo day asc

## Update 2026-06-06 — video_id trong lesson item

Thêm `video_id` vào `mapLesson` (lấy video đầu tiên theo `id` asc). Cập nhật OpenAPI lesson item trong `show`.

**Files**: `ProgramController.php`.
