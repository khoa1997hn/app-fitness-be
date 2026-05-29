# Spec: API chi tiết Program + danh sách bài học (app)

## Bối cảnh

Sau màn Home (`GET /api/v1/programs` — xem [`program-list/spec.md`](../program-list/spec.md)), user chọn 1 program và vào màn **chi tiết program**.
Cần API trả **đầy đủ thông tin program** và **danh sách bài học**, nhóm theo `level` / `special` / `signature`.

> Persona: end-user đã đăng nhập (JWT). DB/model Program/Lesson/Video đã có.

## Ghi chú gốc từ user (raw, không xóa)

- API detail program + các bài học (trả về full mọi thứ, phần bài học group theo level, special, signature) (Nếu trả về list video thì chỉ trả về tên video (tên bài học), và các thông tin khác không bao gồm link xem)

## Phạm vi

### In-scope
- Endpoint `GET /api/v1/programs/{program}` — route model binding `Program`, auth JWT.
- Response **flatten**: field program (giống list) + `lessons` grouped **cùng cấp** trong `data` — không bọc key `program`.
- Tái dùng model/DB hiện có. Không migration mới.

### Out-of-scope
- Gate access theo subscription plan.
- URL/link xem video (`file`, `file.url`).
- Admin CRUD, upload video.

## Nghiệp vụ

- Program không tồn tại → Laravel route model binding → `ModelNotFoundException` → handler common trả 404 `ResponseAPI::error`.
- Lessons nhóm:
  - `lessons.level.beginner` / `intermediate` / `advanced` — chỉ `type=level`.
  - `lessons.special` — `type=special`.
  - `lessons.signature` — `type=signature`.
- Nhóm rỗng → `[]`.
- Sort trong mỗi nhóm: `name` A→Z (translation), tie-break `id` asc.
- Mỗi lesson: `id`, `name`, `description`, `duration_seconds` (sum video theo locale). **Không** trả `file` video.

## API Design

### GET /api/v1/programs/{program}
- **Auth**: Bearer JWT (`auth:api`).
- **Headers**: `x-locale` (optional).
- **Response 200**:
  ```json
  {
    "success": true,
    "message": "Success",
    "data": {
      "id": 1,
      "name": "Yoga",
      "description": "…",
      "cover": { "path": "…", "name": "…", "extension": "jpg", "size": 102400, "url": "…" },
      "rating": 4.5,
      "duration_minutes": 30,
      "course_count": 3,
      "goals": ["…"],
      "lessons": {
        "level": {
          "beginner": [
            { "id": 1, "name": "Bài nhập môn", "description": "…", "duration_seconds": 600 }
          ],
          "intermediate": [],
          "advanced": []
        },
        "special": [],
        "signature": []
      }
    }
  }
  ```
- **Errors**: 401, 404 (model binding), 500.

## Input / Output

### Input
- `Authorization: Bearer <JWT>`.
- `x-locale` (optional).
- Route param `{program}` — id program.

### Output
- `data` — flatten: `id`, `name`, `description`, `cover`, `rating`, `duration_minutes`, `course_count`, `goals` (giống list) + `lessons`.
- `data.lessons.level.beginner|intermediate|advanced` — array lesson.
- `data.lessons.special` / `data.lessons.signature` — array lesson.

## Acceptance criteria

- [ ] Không token → 401.
- [ ] `id` không tồn tại → 404 (model binding + handler).
- [ ] Có token + program hợp lệ → 200, program đủ field, lessons đúng nhóm.
- [ ] Response không có `file` / url video trong lesson.
- [ ] Nhóm rỗng = `[]`. Sort name asc, id asc.

## Quyết định

- 2026-05-29 — Auth? → JWT (`auth:api`), giống list.
- 2026-05-29 — Field program? → Giống list (id, name, description, cover, rating, duration_minutes, course_count, goals).
- 2026-05-29 — 404? → Route model binding `Program`; handler common (`bootstrap/app.php`).
- 2026-05-29 — Sort lesson? → Tên A→Z, tie-break `id` asc.
- 2026-05-29 — Field lesson? → id, name, description, duration_seconds. Không trả file/url video.
- 2026-05-29 — Nhóm rỗng? → `[]`.
- 2026-05-29 — Path? → `GET /api/v1/programs/{program}`.
- 2026-05-29 (update) — Response shape? → Flatten: field program + `lessons` cùng cấp trong `data`, **không** key `program` bọc ngoài.

## Liên quan

- [`program-list/spec.md`](../program-list/spec.md)
- `app/Web/Http/Controllers/API/V1/ProgramController.php`
- `docs/rules/04-api-response.md`, `docs/rules/14-translatable.md`
