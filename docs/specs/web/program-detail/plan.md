# Plan: API chi tiết Program

> Kế hoạch dựa trên `spec.md` cùng folder.

## Tóm tắt

Thêm `ProgramController@show` với route model binding, map `program` (tái dùng logic list) + `lessons` grouped theo type/level. Refactor map program chung cho index/show. Swagger + route.

## Phụ thuộc

- Migration: không
- Model: tái dùng Program, Lesson, Video
- Endpoint mới: `GET /api/v1/programs/{program}`
- Service: không

## Các pha

### Pha 1 — Controller
- `show(Program $program)` + private helpers `mapProgram`, `mapLesson`, `groupLessons`
- Refactor `index` dùng `mapProgram`

### Pha 2 — Route
- `routes/api.php`: `programs/{program}` + `auth:api`

### Pha 3 — OpenAPI
- `#[OA\Get]` cho show + generate

## Verify thủ công

- `GET /api/v1/programs/1` có token → 200, structure đúng
- `GET /api/v1/programs/99999` → 404
- Không có `file` trong lesson items
