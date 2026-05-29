# Task: API yêu thích / bỏ yêu thích bài học

## Pha pre-design

- [x] spec-analyzer + question-asker: chốt qua ASK + figma
- [x] api-designer: API Design trong spec.md
- [x] api-analyzer: route không trùng, auth:api, ResponseAPI nhất quán

## Pha 1 — DB & Model

- [x] Migration `create_lesson_favorites_table` (user_id, lesson_id, timestamps, unique, FK cascade)
- [x] `User::favoriteLessons()` belongsToMany withTimestamps

## Pha 2 — Controller favorites

- [x] `LessonFavoriteController@store` (favorite, idempotent — syncWithoutDetaching)
- [x] `LessonFavoriteController@destroy` (unfavorite, idempotent — detach)
- [x] `LessonFavoriteController@index` (flatten + phân trang + program.days = max day)

## Pha 3 — is_favorited ở program detail

- [x] `ProgramController::show` set `is_favorited` cho lesson item (thread favoritedIds)

## Pha 4 — Route

- [x] `GET lessons/favorites`, `POST/DELETE lessons/{lesson}/favorite` (auth:api)

## Pha OpenAPI

- [x] `#[OA\Get/Post/Delete]` 3 endpoint + program detail lesson item (+is_favorited)
- [x] `l5-swagger:generate`

## Pha review

- [x] reviewer-rules pass (không bịa; naming/DB design đúng; không upload field mới)
- [x] reviewer-smell pass (thread param, không global state; mapFavorite tách riêng)
- [x] reviewer-security pass (auth:api; user-scoped; 404 binding; per_page cap 50)
- [x] reviewer-duplicate pass (mapFavorite khác mapLesson; relation tái dùng)

## Pha cleanup & docs

- [x] cleaner pass
- [x] docs-syncer: `project-overview.md`
- [x] migrate + seed + pint PASS, verify endpoint (idempotent, days, favorited ids)
- [ ] STOP — hỏi user commit/push

## Update 2026-05-29 — Bỏ program, thêm day

- [x] `mapFavorite()`: bỏ `program`, thêm `day`
- [x] `index()`: bỏ eager load program
- [x] OpenAPI + generate
- [x] pint + verify
- [ ] STOP — hỏi user commit/push
