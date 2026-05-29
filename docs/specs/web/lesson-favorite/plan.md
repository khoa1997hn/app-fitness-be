# Plan: API yêu thích / bỏ yêu thích bài học

> Kế hoạch dựa trên `spec.md` cùng folder.

## Tóm tắt

Thêm quan hệ many-to-many user ↔ lesson qua bảng pivot `lesson_favorites`. Tạo `LessonFavoriteController` với 3 action: favorite (POST), unfavorite (DELETE), list favorites (GET, phân trang flatten). Bổ sung `is_favorited` vào lesson item ở program detail.

## Phụ thuộc

- Migration mới: `create_lesson_favorites_table` (user_id, lesson_id, timestamps, unique(user_id,lesson_id), FK cascade)
- Model: `User` (+ relation `favoriteLessons` belongsToMany withTimestamps); tái dùng `Lesson`, `Program`, `Video`
- Controller mới: `App\Web\Http\Controllers\API\V1\LessonFavoriteController`
- Sửa: `ProgramController` (lesson item + `is_favorited`)
- Endpoint mới: `POST/DELETE /api/v1/lessons/{lesson}/favorite`, `GET /api/v1/lessons/favorites`

## Các pha

### Pha 1 — DB & Model
- Migration `create_lesson_favorites_table`
- `User::favoriteLessons()` belongsToMany(Lesson, 'lesson_favorites')->withTimestamps()

### Pha 2 — Controller favorites
- `store(Request, Lesson)` → `syncWithoutDetaching` → 200 data null
- `destroy(Request, Lesson)` → `detach` → 200 data null
- `index(Request)` → favoriteLessons phân trang, mới nhất trước, map flatten + program{id,name,days(max day)}

### Pha 3 — is_favorited ở program detail
- `ProgramController::show` lấy set lesson_id user đã favorite trong program; thread qua `groupLessons → mapLessonsCollection → mapLesson` để set `is_favorited`

### Pha 4 — Route
- Nhóm `auth:api`: `GET lessons/favorites`, `POST/DELETE lessons/{lesson}/favorite`

### Pha 5 — OpenAPI + seeder
- `#[OA\Get/Post/Delete]` cho 3 endpoint; cập nhật program detail lesson item (+is_favorited)
- Seeder: gán vài favorite cho user mẫu (optional, để verify)

## Verify thủ công

- POST favorite 2 lần → 200, chỉ 1 record
- DELETE favorite khi chưa có → 200
- `GET programs/{id}` → lesson item có `is_favorited` đúng
- `GET lessons/favorites` → flatten, có `program.days`=max day, không có link video, phân trang đúng
- Không token → 401; lesson không tồn tại → 404
