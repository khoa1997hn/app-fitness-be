# Plan: video-watch-progress

## Tóm tắt

Migration `user_video_progress`, `VideoWatchProgressService` (lưu + tổng hợp), `POST watch-progress`, bổ sung progress vào Program/Lesson/Favorite/Play APIs.

## Phụ thuộc

- Migration: `user_video_progress`
- Model: `UserVideoProgress`, enum `VideoWatchEvent`
- Service: `VideoWatchProgressService`
- Endpoint mới: `POST /api/v1/videos/{video}/watch-progress`

## Các pha

1. Migration + model + enum + User relation
2. Service + FormRequest + VideoWatchProgressController
3. Cập nhật ProgramController, LessonFavoriteController, VideoPlayController
4. Messages + OpenAPI + pint + migrate

## Update 2026-05-30 — Response shapes + performance

- `watch-progress`: trả `{ video, lesson, program }` mỗi object riêng với progress fields.
- `play`: thêm `lesson` + `program` progress objects trong data.
- Service: `allProgressForUser()` (no filter, programs list), `progressMapForProgram()` (subquery, program detail), `progressMapByVideoIds()` (favorites). Xóa `videoPercentMapForUser(Collection)`.
- OpenAPI: fix intermediate/advanced/special/signature lesson schemas.
