# Plan: API list Program cho màn Home

> Kế hoạch triển khai dựa trên `spec.md` cùng folder.

## Tóm tắt

Tạo nền tảng DB Program/Lesson/Video (translatable pattern, file lưu cột rõ ràng), 2 enum (LessonType, Level), 1 endpoint Web V1 `GET /api/v1/programs` (auth JWT) trả list program cho Home với `duration_minutes` và `course_count` tính động, kèm seeder dev.

## Phụ thuộc

- Migration mới: CÓ — `programs`, `program_translations`, `program_goals`, `program_goal_translations`, `lessons`, `lesson_translations`, `videos`, `video_translations`.
- Model mới: `Program`, `ProgramTranslation`, `ProgramGoal`, `ProgramGoalTranslation`, `Lesson`, `LessonTranslation`, `Video`, `VideoTranslation`.
- Enum mới: `LessonType`, `Level`.
- Endpoint mới: `GET /api/v1/programs` (`ProgramController@index`).
- Endpoint sửa: không.
- View Admin: không.
- Service mới: không (logic list + map đơn giản, Controller-first).
- Package/composer mới: không.
- Seeder: `ProgramsSeeder` đăng ký trong `FakeDatabaseSeeder` (data dev/test).

## Các pha

### Pha 1 — Enum
- Mục tiêu: tạo `LessonType` (level/special/signature), `Level` (beginner/intermediate/advanced).
- Files: `app/Share/Enums/LessonType.php`, `app/Share/Enums/Level.php`.

### Pha 2 — Migration
- Mục tiêu: tạo 8 table theo spec (file lưu cột rõ ràng, KHÔNG jsonb).
- Files: `database/migrations/*_create_programs_table.php` (+ 7 table còn lại, gộp hợp lý theo entity).

### Pha 3 — Model
- Mục tiêu: 8 model + relationship + cast enum + translatable.
- Files: `app/Share/Models/Program.php`, `ProgramTranslation.php`, `ProgramGoal.php`, `ProgramGoalTranslation.php`, `Lesson.php`, `LessonTranslation.php`, `Video.php`, `VideoTranslation.php`.

### Pha 4 — Controller + Route
- Mục tiêu: `ProgramController@index` query + map response (duration_minutes, course_count, cover object, goals); route auth:api.
- Files: `app/Web/Http/Controllers/API/V1/ProgramController.php`, `routes/api.php`.

### Pha 5 — Swagger
- Mục tiêu: annotation `#[OA\Get]` khớp mapping field + `l5-swagger:generate`.
- Files: `ProgramController.php`.

### Pha 6 — Seeder
- Mục tiêu: seed 7 program + vài lesson/video mỗi program, cả vi/en.
- Files: `database/seeders/ProgramsSeeder.php`, `database/seeders/FakeDatabaseSeeder.php`.

## Rủi ro

- Sum `duration_seconds` theo locale: phải eager-load đúng translation video để tránh N+1 và sai locale → dùng `withTranslation()` + tính trong PHP theo current locale.
- `course_count` đếm lessons: dùng `withCount('lessons')` tránh N+1.
- Translatable cần translation model cùng namespace `App\Share\Models` (config suffix `Translation`).

## Verify thủ công

- `sail artisan migrate` chạy sạch.
- `sail artisan db:seed --class=FakeDatabaseSeeder` tạo data.
- `GET /api/v1/programs` không token → 401.
- Có token → 200, list program đúng field, có `duration_minutes`/`course_count`, KHÔNG có video link.
- Đổi header `x-locale: en` → name/description/cover/goals đổi sang en.

## Update 2026-05-29

### Tóm tắt
Bổ sung `FileType::ProgramCover` + `FileType::LessonVideo` và entry `config/app_file.php` theo rule `12-file-upload.md`. Cập nhật seeder path khớp `prefix_path`. Không đổi API/DB schema.

### Phụ thuộc
- `app/Share/Enums/FileType.php` — thêm 2 const
- `config/app_file.php` — thêm 2 entry
- `database/seeders/ProgramsSeeder.php` — path mẫu `program/cover`, `lesson/video`

### Verify
- `config('app_file.program_cover')` và `config('app_file.lesson_video')` trả đủ 3 key
- Seeder path khớp prefix
