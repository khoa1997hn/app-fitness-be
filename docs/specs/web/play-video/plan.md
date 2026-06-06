# Plan: API phát video (play-video)

## Tóm tắt

Service kiểm tra quyền xem video theo subscription + program selection + lesson type; controller cấp presigned GET qua `FileUploadService`. Hai endpoint play và refresh-stream dùng chung logic.

## Phụ thuộc

- Migration: không
- Model: `Video`, `Lesson`, `Subscription`, `SubscriptionProgramSelection`
- Service mới: `VideoPlayService`
- Endpoint: `POST /api/v1/videos/{video}/play`, `POST /api/v1/videos/{video}/refresh-stream`

## Các pha

### Pha 1 — VideoPlayService
- `canStream(User, Video): ?string` — null = OK, string = message key đã dịch
- `createStreamUrl(Video): string` — presigned GET từ `file.path`

### Pha 2 — Controller + routes
- `VideoPlayController@play`, `@refreshStream`
- `routes/api.php` trong `auth:api`

### Pha 3 — i18n + OpenAPI
- `lang/vi/messages.php`
- `l5-swagger:generate`

## Verify thủ công

- Subscription active + đủ quyền → 200 `stream_url`
- Không subscription → 403
- Basic + signature lesson → 403

## Update 2026-05-29

- Bỏ `refresh-stream`; mở rộng response `play` (id, lesson_id, duration_seconds, file, stream_url).
- `auth()->user()` trong controller; rule `04-api-response.md`.
- OpenAPI + regenerate.

## Update 2026-06-06

- Bỏ `stream_url` khỏi response `play` — presigned GET chỉ qua `file.url`.
- Xóa `createStreamUrl()` khỏi `VideoPlayService` (không còn dùng).
- Cập nhật OpenAPI + `video-watch-progress` spec (bỏ nhắc `stream_url`).

### Verify thủ công
- Subscription active + đủ quyền → 200, `data.file.url` playable (không có `stream_url`).
