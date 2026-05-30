# Spec: Tiến độ xem video (watch progress)

## Bối cảnh

App native gọi API khi user **bắt đầu**, **đang xem** và **kết thúc** video để BE lưu % đã xem theo video. BE tổng hợp lên **lesson** và **program** (có trọng số theo `duration_seconds` của từng video).

Trước đây Figma program detail ghi FE lưu local — **đổi sang BE** vì cần đồng bộ đa thiết bị và hiển thị progress trên Home / program detail / favorites / play.

Tham chiếu mockup (khi có trong repo): `docs/specs/web/figma/program_detail_*.png` — card bài học có trạng thái tiến độ; program có % tổng.

## Ghi chú gốc từ user (raw, không xóa)

API để app liên tục gọi khi user start, đang xem và kết thúc xem video, lưu phần trăm đã xem video → tổng hợp lesson, program. Các API program/lesson/video đã có trả thêm % hoàn thành. Auth user từ token, không truyền user_id. Module Web (app).

update: chỉ cần truyền `watched_percent`, không cần event. Response trả flat vào object video/lesson tương ứng, không bọc key `progress`. *(Update 2026-05-30)*

## Phạm vi

### In-scope
- Bảng `user_video_progress` (`user_id`, `video_id`, `watched_percent` 0–100).
- `POST /api/v1/videos/{video}/watch-progress` — JWT, body chỉ `watched_percent` (0–100); **không** trường `event` *(Update 2026-05-30)*.
- Lưu `max(percent hiện tại, percent gửi lên)` — không giảm khi user tua lại.
- Response: `{ video: {..., watched_percent}, lesson: {id, watched_percent}, program: {id, watched_percent} }` — mỗi level object riêng, chỉ `watched_percent`, **không** `is_completed` *(Update 2026-05-30)*.
- Bổ sung `watched_percent` vào:
  - `GET /api/v1/programs` (mỗi program)
  - `GET /api/v1/programs/{program}` (program + từng lesson)
  - `GET /api/v1/lessons/favorites` (mỗi item)
  - `POST /api/v1/videos/{video}/play` (video + lesson + program progress)
- User từ `auth()->user()` — **không** nhận `user_id` từ client.

### Out-of-scope
- Resume position giây (chỉ % tổng).
- Lịch sử từng lần xem / analytics.
- Admin xem progress user.

## Nghiệp vụ

### Ghi progress
1. App gọi `POST watch-progress` định kỳ khi xem (start/heartbeat/end — app tự quyết tần suất).
2. Validate `watched_percent` 0–100 integer.
3. Upsert theo `(user_id, video_id)`.
4. Trả `{ video, lesson, program }` mỗi object riêng với `watched_percent`.

### Field bổ sung trên API đọc
- `GET /programs`, `GET /programs/{program}`: program + mỗi lesson item thêm `watched_percent`.
- `GET /lessons/favorites`: mỗi item thêm `watched_percent`.

### Tổng hợp lesson / program (service — tính bằng SQL)
- **Lesson**: `ROUND(SUM(COALESCE(watched_percent,0) * duration_seconds) / NULLIF(SUM(duration_seconds), 0))` trên các videos của lesson.
- **Program**: cùng công thức, join `videos → lessons → program_id`.
- Video chưa có progress → 0%.
- `duration_seconds = 0` → bỏ qua khỏi mẫu.
- Tính batch qua `programPercentMapForUser(User, array $programIds)` / `lessonPercentMapForUser(User, array $lessonIds)` — 1 SQL cho nhiều ID.

### Lỗi
| Trường hợp | HTTP |
|------------|------|
| Không JWT | 401 |
| Video không tồn tại | 404 |
| Validation | 422 |

## Input / Output

### POST /api/v1/videos/{video}/watch-progress
Request:
```json
{ "watched_percent": 42 }
```

Response 200 *(Update 2026-05-30)*:
```json
{
  "success": true,
  "message": "Success",
  "data": {
    "video": { "id": 5, "lesson_id": 10, "duration_seconds": 600, "watched_percent": 42 },
    "lesson": { "id": 10, "watched_percent": 35 },
    "program": { "id": 1, "watched_percent": 12 }
  }
}
```

### POST /api/v1/videos/{video}/play (progress section)
Ngoài `stream_url` + metadata *(Update 2026-05-30)*:
```json
{
  "data": {
    "id": 1, "lesson_id": 10, "duration_seconds": 600, "file": {...}, "stream_url": "...",
    "watched_percent": 42,
    "lesson": { "id": 10, "watched_percent": 35 },
    "program": { "id": 1, "watched_percent": 12 }
  }
}
```

## Acceptance criteria

- [ ] POST progress với JWT → lưu DB, trả `{ video, lesson, program }` mỗi cái có `watched_percent`.
- [ ] Gọi lại với % thấp hơn → không giảm % đã lưu.
- [ ] GET programs / program detail / favorites có `watched_percent`.
- [ ] POST play trả `watched_percent` trong video + `lesson` + `program` objects.

## Quyết định

- **2026-05-30** — Ngưỡng hoàn thành 90% đã được thiết kế (`is_completed`) nhưng **bị bỏ** — xem quyết định 2026-05-30 bên dưới.
- **2026-05-30** — Tổng hợp lesson/program: **có trọng số** theo `duration_seconds`.
- **2026-05-30** — Figma program detail trước ghi FE local → **BE** lưu và trả progress (cập nhật `program-detail` behavior).
- **2026-05-30** — Không endpoint refresh riêng; heartbeat qua `watch-progress`.
- **2026-05-30** — Bỏ field `event`; chỉ `watched_percent`. Enum `VideoWatchEvent` xóa.
- **2026-05-30** — `watch-progress` trả `{ video, lesson, program }` mỗi level object riêng. `play` thêm `lesson` + `program`.
- **2026-05-30** — Performance: `videoPercentMapForUser(Collection)` → `allProgressForUser()` (no filter, cho list) + `progressMapForProgram()` (subquery theo program_id).
- **2026-05-30** — Bỏ `is_completed`; chỉ trả `watched_percent`. Bỏ `mapProgressFields`. Tính lesson/program percent qua SQL (không load collection videos/lessons vào memory).

## Liên quan

- [`program-detail/spec.md`](../program-detail/spec.md)
- [`program-list/spec.md`](../program-list/spec.md)
- [`play-video/spec.md`](../play-video/spec.md)
- [`lesson-favorite/spec.md`](../lesson-favorite/spec.md)
