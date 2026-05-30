# Spec: Program đang học dở (Left Off)

## Bối cảnh

Màn hình Home của app hiển thị widget "Pick up where you left off" — trả về program gần nhất mà user đang học dở (chưa hoàn thành 100%), kèm bài học cuối cùng user xem và video cụ thể trong bài đó.

Dữ liệu "học dở" dựa vào bảng `user_video_progress.last_watched_at` — field vừa được thêm ở sprint trước.

Figma: `docs/specs/web/figma/left_off_program.png`

## Phạm vi

### In-scope
- API `GET /api/v1/programs/left-off` — trả về program xem gần nhất (kể cả đã hoàn thành 100%).
- Khi user chưa có watch history → trả `data: null`.

### Out-of-scope
- Danh sách nhiều program đang học dở (chỉ lấy 1 cái mới nhất).
- Logic "next video" hay gợi ý video tiếp theo (chưa làm).

## Nghiệp vụ

1. Lấy tất cả `user_video_progress` của user auth, join qua `videos → lessons → programs`.
2. Group theo `program_id`, tính `completed_percent` (theo đúng công thức đã có: `ROUND(COUNT(is_completed=true) / COUNT(all_videos) * 100)`).
3. Sắp xếp theo `MAX(last_watched_at) DESC`, lấy top 1 → đây là program xem gần nhất (kể cả đã hoàn thành 100%).
5. Trong program đó, tìm video có `last_watched_at` lớn nhất → đây là `last_lesson.video`.
6. Trả về program info + `progress` + `last_lesson` (kèm `video`).

**Nếu user chưa xem video nào** → không có bản ghi `user_video_progress` → trả `data: null`.

## Input / Output

### Input
- Auth JWT (header `Authorization: Bearer <token>`) — bắt buộc.
- Header `x-locale` (optional, default `vi`).

### Output (khi có data)

```json
{
  "id": 1,
  "name": "Mat Pilates",
  "cover": {
    "url": "https://...",
    "path": "programs/cover.jpg"
  },
  "duration_seconds": 1800,
  "progress": {
    "watched_seconds": 360,
    "completed_percent": 20
  },
  "last_lesson": {
    "id": 12,
    "name": "Glutes & Core",
    "day": 12,
    "thumbnail": {
      "url": "https://...",
      "path": "lessons/thumbnail.jpg"
    },
    "video": {
      "id": 45,
      "duration_seconds": 600
    }
  }
}
```

**`duration_seconds` (program level)**: tổng `duration_seconds` của toàn bộ videos thuộc program đó (tất cả lessons).

**`last_lesson.video`**: video có `last_watched_at` mới nhất của user trong program đó.

### Output (khi không có data)

```json
{
  "success": true,
  "message": "...",
  "data": null
}
```

## Acceptance criteria

- [ ] User đã xem 1 video trong program A → API trả về program A với `last_lesson` đúng lesson chứa video đó.
- [ ] User đã xem video trong 2 program (A và B), gần đây nhất là B → API trả về B.
- [ ] Program A đã hoàn thành 100% và có `last_watched_at` mới hơn B → API vẫn trả về A (không lọc bỏ).
- [ ] User chưa xem video nào → API trả 200 với `data: null`.
- [ ] `duration_seconds` là tổng duration tất cả videos trong program.
- [ ] `progress.completed_percent` tính đúng theo công thức `COUNT(is_completed=true) / COUNT(all_videos) * 100`.

## Quyết định

- **2026-05-30** — Endpoint URL → `GET /api/v1/programs/left-off`.
- **2026-05-30** — "30 min" trong Figma → `duration_seconds` tổng toàn bộ videos của program.
- **2026-05-30** — Khi user chưa có watch history → HTTP 200 với `data: null`.
- **2026-05-30** — "Học dở" filter → **không lọc** — trả về program có `last_watched_at` mới nhất bất kể `completed_percent` (kể cả 100%).
- **2026-05-30** — `last_lesson.video` → video có `last_watched_at` mới nhất của user trong program đó.

## API Design

### GET /api/v1/programs/left-off
- **Auth**: required (Bearer JWT)
- **Request**: không có body/query param. Header `x-locale` optional (default `vi`).
- **Response 200** (có data):
  ```json
  {
    "success": true,
    "message": "...",
    "data": {
      "id": 1,
      "name": "Mat Pilates",
      "cover": { "url": "...", "path": "..." },
      "duration_seconds": 1800,
      "progress": {
        "watched_seconds": 360,
        "completed_percent": 20
      },
      "last_lesson": {
        "id": 12,
        "name": "Glutes & Core",
        "day": 12,
        "thumbnail": { "url": "...", "path": "..." },
        "video": {
          "id": 45,
          "duration_seconds": 600
        }
      }
    }
  }
  ```
- **Response 200** (không có watch history): `data: null`
- **Errors**: 401

## Update 2026-05-30 — Trả về toàn bộ programs đã học (thay vì 1)

### Thay đổi phạm vi

- **Bỏ**: trả về 1 program mới nhất.
- **Mới**: trả về **toàn bộ programs** user đã có watch progress, sort theo `MAX(last_watched_at) DESC`.
- Khi user chưa xem video nào → `data: []` (array rỗng thay vì `null`).

### Nghiệp vụ (cập nhật)

1. Lấy tất cả `user_video_progress` của user auth, join qua `videos → lessons → programs`.
2. Group theo `program_id`, tính `completed_percent`, `watched_seconds`, `total_duration_seconds`, `MAX(last_watched_at)`.
3. Sắp xếp theo `MAX(last_watched_at) DESC`.
4. Trong mỗi program, tìm video có `last_watched_at` lớn nhất (dùng window function hoặc subquery) → đây là `last_lesson.video`.
5. Trả về array các program với đầy đủ `progress` + `last_lesson`.

### API Design (cập nhật)

`data` đổi từ object/null → array (có thể rỗng):

```json
{
  "success": true,
  "message": "...",
  "data": [
    {
      "id": 1,
      "name": "Mat Pilates",
      "cover": { "url": "...", "path": "..." },
      "duration_seconds": 1800,
      "progress": { "watched_seconds": 360, "completed_percent": 20 },
      "last_lesson": {
        "id": 12,
        "name": "Glutes & Core",
        "day": 12,
        "thumbnail": { "url": "...", "path": "..." },
        "video": { "id": 45, "duration_seconds": 600 }
      }
    }
  ]
}
```

### Quyết định

- **2026-05-30** — Đổi response từ single object → array. Không cần pagination (max 7 programs).
- **2026-05-30** — Khi không có watch history → `data: []` (array rỗng, không phải `null`).

- [`video-watch-progress/spec.md`](../video-watch-progress/spec.md)
- [`program-list/spec.md`](../program-list/spec.md)
- [`program-detail/spec.md`](../program-detail/spec.md)
