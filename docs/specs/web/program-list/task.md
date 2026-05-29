# Task: API list Program cho màn Home

> Checklist atomic. Mỗi task ≤ 1 đơn vị code. Tick khi xong.

## Pha pre-design

- [x] solution-reviewer: review proposal solution của user
- [x] api-designer: section "API Design" trong spec.md đã được user duyệt

## Pha 1 — Enum

- [x] Tạo enum `app/Share/Enums/LessonType.php` (Level/Special/Signature)
- [x] Tạo enum `app/Share/Enums/Level.php` (Beginner/Intermediate/Advanced)

## Pha 2 — Migration

- [x] Migration tạo `programs` + `program_translations`
- [x] Migration tạo `program_goals` + `program_goal_translations`
- [x] Migration tạo `lessons` + `lesson_translations`
- [x] Migration tạo `videos` + `video_translations`

## Pha 3 — Model

- [x] Tạo `Program` + `ProgramTranslation` (translatable: name, description, cover_*, sort; hasMany lessons, goals)
- [x] Tạo `ProgramGoal` + `ProgramGoalTranslation` (translatable: content; belongsTo program)
- [x] Tạo `Lesson` + `LessonTranslation` (translatable: name, description; cast type/level enum; belongsTo program, hasMany videos)
- [x] Tạo `Video` + `VideoTranslation` (translatable: file_*, duration_seconds; belongsTo lesson)

## Pha 4 — Controller + Route

- [x] Tạo `ProgramController@index` — query + map response (cover object, goals, duration_minutes, course_count)
- [x] Đăng ký route `GET /api/v1/programs` (middleware `auth:api`) trong `routes/api.php`

## Pha OpenAPI (chạm endpoint Web V1)

- [x] openapi-writer cập nhật `#[OA\Get]` attribute khớp mapping field
- [x] `php artisan l5-swagger:generate` chạy thành công

## Pha 6 — Seeder

- [x] Tạo `ProgramsSeeder` (7 program + lesson/video mẫu, vi/en)
- [x] Đăng ký `ProgramsSeeder` trong `FakeDatabaseSeeder`

## Pha review

- [x] reviewer-rules pass (1 divergence file-columns vs rule 12 — user-approved)
- [x] reviewer-smell pass
- [x] reviewer-security pass
- [x] reviewer-duplicate pass + fix

## Pha cleanup

- [x] cleaner: file rác đã xóa (file artisan generate sai path)
- [x] cleaner: code rác (import/biến/method 0-reference) đã xóa
- [x] cleaner: `.env` ↔ `.env.example` đồng bộ key (không có env mới)
- [x] cleaner: route / view / translation rác đã xóa hoặc đã hỏi user

## Pha docs sync

- [x] docs-syncer: `project-overview.md` đã reflect module mới (Program/Lesson/Video)
- [x] docs-syncer: KHÔNG đổi rule file (user chốt giữ pattern File jsonb hiện có — rule 10/12 nguyên)
- [x] docs-syncer: rules / guides / agents đã đồng bộ (không có thay đổi workflow)

## Pha finalize

- [x] Chạy migration
- [x] Chạy `pint` (PASS 116 files)
- [x] Verify thủ công: endpoint trả đúng (7 program, locale, duration_minutes/course_count, không link video)
- [ ] STOP — hỏi user commit/push
