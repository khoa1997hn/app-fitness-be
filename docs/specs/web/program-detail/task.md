# Task: API chi tiết Program

## Pha pre-design

- [x] question-asker: spec đã clear
- [x] api-designer: API Design trong spec.md

## Pha 1 — Controller

- [x] Refactor `mapProgram` dùng chung cho `index` và `show`
- [x] Thêm `show(Program $program)` + `mapLesson` + `groupLessons`

## Pha 2 — Route

- [x] Đăng ký `GET programs/{program}` + `auth:api`

## Pha OpenAPI

- [x] `#[OA\Get]` path `/programs/{program}`
- [x] `l5-swagger:generate`

## Pha review

- [x] reviewer-rules pass
- [x] reviewer-smell pass
- [x] reviewer-duplicate pass (mapProgram shared)
- [x] reviewer-security pass

## Pha cleanup & docs

- [x] cleaner pass
- [x] docs-syncer: `project-overview.md`
- [x] pint PASS
- [x] STOP — hỏi user commit/push

## Update 2026-05-29 — Flatten response

- [x] Sửa `show()` spread `mapProgram` + `lessons`
- [x] Cập nhật OpenAPI `data` flatten
- [x] Cập nhật `spec.md`
- [x] `l5-swagger:generate` + pint
- [x] STOP — hỏi user commit/push

## Update 2026-05-29 — Figma (thumbnail + day)

- [x] Migration `add_day_to_lessons_table` (unsignedInteger, default 1)
- [x] Migration `add_thumbnail_to_lesson_translations_table` (jsonb nullable)
- [x] Lesson model: + `day` (fillable/cast/PHPDoc), + `thumbnail` (translated)
- [x] LessonTranslation: + `thumbnail` FileCast
- [x] `FileType::LessonThumbnail` + entry `config/app_file.php`
- [x] Controller: `mapLesson` + day/thumbnail; `sortLessons` theo day asc → id asc
- [x] OpenAPI cập nhật lesson item + generate
- [x] Seeder: set day + thumbnail
- [x] reviewer-rules / smell / security / duplicate pass
- [x] cleaner / docs-syncer pass
- [x] migrate + pint PASS, verify endpoint
- [ ] STOP — hỏi user commit/push
