# Plan: API profile user — bổ sung favorited_programs

## Tóm tắt

Bổ sung `favorited_programs` vào response `GET /api/v1/auth/profile`. Reuse trait `MapsProgramForApi` để tính `duration_minutes`/`course_count`, map subset 6 field. Không migration, không route mới.

## Phụ thuộc

- `program_favorites` pivot + `User::favoritePrograms()` (đã có).
- `MapsProgramForApi::mapProgram()` + `programRelations()` (đã có).

## Các pha

### Pha 1 — ProfileController logic
- File: `app/Web/Http/Controllers/API/V1/Auth/ProfileController.php`
- `use MapsProgramForApi`
- Trong `show()`: load `favoritePrograms()` với translation + lessons/videos, `orderByPivot('created_at', 'desc')`
- Map mỗi program → 6 field basic, gán vào `favorited_programs`

### Pha 2 — OpenAPI
- Cập nhật `#[OA\Get]` response schema: thêm `favorited_programs` array
- Regenerate `api-docs.json`

## Rủi ro

- N+1 nếu không eager load đủ relation — mitigate bằng `with($this->programRelations())` giống `ProgramController`.

## Verify thủ công

- User không favorite → `favorited_programs: []`
- User favorite 2+ program → thứ tự mới nhất trước
- Response không có goals/progress/is_favorited trong program item
