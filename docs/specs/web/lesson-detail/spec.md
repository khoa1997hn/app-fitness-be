# Spec: API chi tiết Lesson (app)

## Bối cảnh

Sau khi user xem danh sách bài học trong program detail (`GET /api/v1/programs/{program}`) hoặc từ màn khác, cần API trả **thông tin chi tiết 1 bài học** để hiển thị màn lesson detail trước khi phát video (`POST /api/v1/videos/{video}/play`).

> Persona: end-user đã đăng nhập (JWT). Model `Lesson` / `Video` / `Program` đã có.

## Ghi chú gốc từ user (raw, không xóa)

- thêm API detail lesson

## Phạm vi

### In-scope
- Endpoint `GET /api/v1/lessons/{lesson}` — route model binding `Lesson`, auth JWT.
- Tái dùng model/DB hiện có. Không migration mới.

### Out-of-scope
- URL/link xem video (`file`, `file.url`) — vẫn qua `POST /api/v1/videos/{video}/play`.
- Gate access theo subscription plan (gate chỉ ở `POST /api/v1/videos/{video}/play`).
- Thông tin program nested trong response.
- Admin CRUD, upload video.

## Nghiệp vụ

- Lesson không tồn tại → Laravel route model binding → 404.
- Không token / token sai → 401.
- Header `x-locale` (optional) — field translated theo locale hiện tại.
- `video_id` = id video đầu tiên của lesson (`videos` sort `id` asc). Nullable nếu chưa có video.
- `duration_seconds` = sum `duration_seconds` của tất cả videos lesson (theo locale).
- `is_favorited` = user hiện tại đã yêu thích lesson này chưa.
- `progress` = tiến độ xem lesson của user (`watched_seconds`, `completed_percent`).
- Không trả `type`, `level`, thông tin program.

## API Design

### GET /api/v1/lessons/{lesson}
- **Auth**: Bearer JWT (`auth:api`).
- **Headers**: `x-locale` (optional).
- **Response 200**:
  ```json
  {
    "success": true,
    "message": "Success",
    "data": {
      "id": 12,
      "video_id": 10,
      "day": 1,
      "name": "Day 1 - Warm up",
      "description": "…",
      "teacher_name": "Jane Doe",
      "thumbnail": { "path": "…", "name": "…", "extension": "jpg", "size": 102400, "url": "…" },
      "duration_seconds": 600,
      "is_favorited": false,
      "progress": { "watched_seconds": 0, "completed_percent": 0 }
    }
  }
  ```
- **Errors**: 401, 404 (model binding), 500.

## Input / Output

### Input
- `Authorization: Bearer <JWT>`.
- `x-locale` (optional).
- Route param `{lesson}` — id lesson.

### Output
- `data.id` (int) — id lesson.
- `data.video_id` (int|null) — id video để gọi play.
- `data.day` (int) — thứ tự ngày tập.
- `data.name` (string) — tên bài học (locale).
- `data.description` (string|null).
- `data.teacher_name` (string|null).
- `data.thumbnail` (File|null).
- `data.duration_seconds` (int).
- `data.is_favorited` (bool).
- `data.progress` — `{ watched_seconds, completed_percent }`.

## Acceptance criteria

- [ ] Không token → 401.
- [ ] `id` lesson không tồn tại → 404.
- [ ] Có token + lesson hợp lệ → 200, đủ field như trên.
- [ ] Response không có `file` / url video, không có `program`, `type`, `level`.
- [ ] `is_favorited` và `progress` đúng theo user hiện tại.

## Quyết định

- **2026-06-07** — Response fields? → Giống lesson item program detail: `id, video_id, day, name, description, teacher_name, thumbnail, duration_seconds, is_favorited, progress`. Không kèm program, type, level.
- **2026-06-07** — Gate subscription? → Không gate (giống program detail; gate chỉ ở `POST /videos/{video}/play`).
- **2026-06-07** — Path? → `GET /api/v1/lessons/{lesson}`.

## Liên quan

- [`program-detail/spec.md`](../program-detail/spec.md)
- [`lesson-favorite/spec.md`](../lesson-favorite/spec.md)
- `app/Web/Http/Controllers/API/V1/ProgramController.php` — `mapLesson()`
- `app/Share/Models/Lesson.php`
