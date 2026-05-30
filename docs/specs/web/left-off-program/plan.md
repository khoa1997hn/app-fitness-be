# Plan: Program đang học dở (Left Off)

> Kế hoạch triển khai dựa trên `spec.md` cùng folder.

## Tóm tắt

Thêm endpoint `GET /api/v1/programs/left-off` (auth JWT). Không cần migration mới. Logic hoàn toàn trong controller, dùng một SQL query để tìm program có `last_watched_at` mới nhất của user và tính `progress`. Logic đủ phức tạp (~40 dòng) → đặt vào `VideoWatchProgressService`.

## Phụ thuộc

- Migration mới: không
- Model mới: không
- Endpoint mới: `GET /api/v1/programs/left-off`
- Endpoint sửa: không
- View Admin mới/sửa: không
- Service mới: không — thêm method vào `VideoWatchProgressService`
- Package/composer mới: không

## Các pha

### Pha 1 — Service method

- Mục tiêu: thêm `leftOffProgram(User): ?array` vào `VideoWatchProgressService`. Method này chạy SQL để tìm program mới nhất được xem, tính `completed_percent`, `watched_seconds`, tổng `duration_seconds` của program, và lấy video có `last_watched_at` mới nhất.
- Files dự kiến: `app/Share/Services/Video/VideoWatchProgressService.php`

### Pha 2 — Controller + Route

- Mục tiêu: tạo `ProgramLeftOffController` extend `APIController`, gọi service, trả `ResponseAPI::success()`. Thêm route `GET programs/left-off`.
- Files dự kiến:
  - `app/Web/Http/Controllers/API/V1/ProgramLeftOffController.php`
  - `routes/api.php`

### Pha 3 — OpenAPI

- Mục tiêu: thêm `#[OA\...]` annotation cho endpoint mới.
- Files dự kiến: `app/Web/Http/Controllers/API/V1/ProgramLeftOffController.php`

## Rủi ro

- Query phức tạp: join nhiều bảng + subquery. Cần test với user có nhiều watch progress. Phòng: viết SQL rõ ràng, dùng index có sẵn (`user_id`, `video_id`, `last_watched_at`).
- `duration_seconds` là translatable field của `Video` → phải join `video_translations` để sum. Phòng: dùng `DB::table` với join bảng translation.

## Verify thủ công

1. User chưa xem video nào → `data: null`.
2. User xem video thuộc program A → trả về program A với đúng `last_lesson` và `video`.
3. User xem video thuộc A rồi B → trả về B.
4. `completed_percent` đúng theo công thức.
5. `duration_seconds` program = tổng tất cả videos.

## Update 2026-05-30 — Array thay vì single object

Đổi `leftOffProgram` trả về `array` các program (tất cả đã học), sort DESC theo `last_watched_at`. Dùng window function `ROW_NUMBER() OVER (PARTITION BY program_id ORDER BY last_watched_at DESC)` để lấy video mới nhất mỗi program trong 1 query. Stats (progress + duration) load batch theo danh sách `program_id` thu được. Model load bằng `whereIn`.

**Files**: `VideoWatchProgressService.php`, `ProgramLeftOffController.php`.
