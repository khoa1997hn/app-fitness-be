# Spec: Combo — Admin CRUD + API list/detail

## Bối cảnh

App native cần hiển thị **combo** — gói gồm nhiều program (bộ môn) kèm tên combo, ảnh cover và tối đa 3 thông tin bổ sung (mỗi thông tin: icon PNG dùng chung + text đa ngôn ngữ). Admin cần màn hình chọn các program thuộc combo và quản lý nội dung combo.

> Persona end-user: đã đăng nhập (JWT), xem danh sách combo và chi tiết combo trên app.
> Persona admin: vận hành nội dung, tạo/sửa/xóa combo và gán program.

## Phạm vi

### In-scope
- Model + migration: `Combo` + `combo_translations`, pivot `combo_program`, `ComboInfo` + `combo_info_translations`.
- `FileType`: `ComboCover`, `ComboInfoIcon` (PNG only cho icon).
- Admin full CRUD: list (20/trang, sort `id` DESC), create, edit, delete.
- Web API V1: `GET /api/v1/combos`, `GET /api/v1/combos/{combo}` (auth JWT).
- OpenAPI annotation cho 2 endpoint Web V1.

### Out-of-scope
- Subscription / mua combo / gate access theo combo (quyền xem program vẫn theo subscription hiện tại).
- Favorite combo.
- `is_active` — mọi combo đều hiển thị trên app.
- Import/export.

## Nghiệp vụ

- **Combo** gồm 2–7 **Program** do admin chọn (thứ tự lưu trên pivot `sort`).
- Mỗi combo có **tên** + **cover** (đa ngôn ngữ vi/en).
- **Thông tin bổ sung**: tối đa 3 mục (`sort` 0→2); mỗi mục: **icon** PNG (dùng chung mọi locale) + **text** (đa ngôn ngữ, max 100 ký tự).
- App list: card tối giản. App detail: full program detail giống `GET /programs/{program}` cho từng program trong combo.
- Sort list combo: combo chứa ≥1 program user đã yêu thích lên trước (theo thời điểm favorite program mới nhất trong combo), còn lại theo `id` DESC.

## Mô hình dữ liệu

### `combos`
- `id`, `timestamps`

### `combo_translations`
- `id`, `combo_id` FK→combos cascadeOnDelete
- `locale` index, unique(`combo_id`,`locale`)
- `name` string NOT NULL
- `cover` jsonb nullable — File (`FileType::ComboCover`)
- KHÔNG timestamps

### `combo_program` (pivot)
- `id`
- `combo_id` FK→combos cascadeOnDelete
- `program_id` FK→programs cascadeOnDelete
- `sort` unsignedSmallInteger default 0
- unique(`combo_id`, `program_id`)

### `combo_infos`
- `id`, `combo_id` FK→combos cascadeOnDelete
- `sort` unsignedTinyInteger (0–2)
- `icon` jsonb NOT NULL — File (`FileType::ComboInfoIcon`, PNG only)
- unique(`combo_id`, `sort`)
- KHÔNG timestamps

### `combo_info_translations`
- `id`, `combo_info_id` FK→combo_infos cascadeOnDelete
- `locale` index, unique(`combo_info_id`,`locale`)
- `text` string(100) NOT NULL
- KHÔNG timestamps

### File upload

| Field | FileType | prefix | mime | max |
|-------|----------|--------|------|-----|
| cover | `ComboCover` | `combo/cover` | jpeg,png,jpg,webp | 5120 KB |
| info icon | `ComboInfoIcon` | `combo/info-icon` | **png only** | 5120 KB |

## API Design

### GET /api/v1/combos
- **Auth**: Bearer JWT (`auth:api`).
- **Headers**: `x-locale` (optional).
- **Query**: none. Không phân trang.
- **Sort**: combo có program user đã favorite lên trước (favorite mới nhất trước); nhóm còn lại `id` DESC.
- **Response 200** — mỗi item:
  ```json
  {
    "id": 1,
    "name": "Yoga & Pilates",
    "cover": { "path": "...", "name": "...", "extension": "jpg", "size": 12345, "url": "..." },
    "program_count": 3,
    "infos": [
      { "icon": { "path": "...", "url": "..." }, "text": "Giảm stress" }
    ]
  }
  ```
- **Errors**: 401, 500.

### GET /api/v1/combos/{combo}
- **Auth**: Bearer JWT.
- **Headers**: `x-locale` (optional).
- **Response 200**:
  ```json
  {
    "id": 1,
    "name": "...",
    "cover": { ... },
    "program_count": 3,
    "infos": [ { "icon": { ... }, "text": "..." } ],
    "programs": [
      {
        "id": 1,
        "name": "Yoga",
        "description": "...",
        "cover": { ... },
        "rating": 4.5,
        "duration_minutes": 30,
        "course_count": 12,
        "goals": ["..."],
        "progress": { "watched_seconds": 0, "completed_percent": 0 },
        "lessons": { "level": { "beginner": [], "intermediate": [], "advanced": [] }, "special": [], "signature": [] }
      }
    ]
  }
  ```
  - Mỗi `programs[]` = response `GET /programs/{program}` (cùng field, cùng sort lesson).
  - `programs` sort theo pivot `sort` asc.
- **Errors**: 401, 404 (model binding), 500.

## Admin

- Route: `Route::resource('combos', ComboController::class)` — index/create/store/edit/update/destroy.
- List: phân trang 20/trang, `id` DESC; cột tên (locale vi), số program, thao tác.
- Form create/edit:
  - `translations[vi|en][name]` — required vi
  - `translations[vi|en][cover]` — presigned upload, required vi
  - `program_ids[]` — checkbox/multi-select, min 2 max 7, distinct
  - `infos[]` — max 3: `icon` (presigned PNG) + `translations[vi|en][text]` (required vi, max 100)
- Xóa: hard delete + confirm JS.
- Menu sidebar: **Combo**.

## Input / Output

### Input (Admin)
- `translations[<locale>][name]`: string, required với `vi`, max 255
- `translations[<locale>][cover][path|name|extension|size]`: required với `vi`
- `program_ids`: array int, min 2, max 7, distinct, exists programs
- `infos`: array max 3; mỗi item:
  - `icon[path|name|extension|size]`: required, PNG
  - `translations[<locale>][text]`: required vi, max 100

### Output (API list item)
- `id`, `name`, `cover`, `program_count`, `infos[]` (`icon`, `text`)

### Output (API detail)
- List fields + `programs[]` (full program detail)

## Acceptance criteria

- [ ] Admin CRUD combo: tên, cover, chọn 2–7 program, ≤3 info (icon PNG + text)
- [ ] Validate: <2 hoặc >7 program → 422; >3 info → 422; text >100 → 422
- [ ] `GET /api/v1/combos` auth JWT, sort favorite-first, không phân trang
- [ ] `GET /api/v1/combos/{combo}` trả programs full detail
- [ ] `x-locale` đổi name/cover/text
- [ ] OpenAPI khớp response

## Quyết định

- 2026-06-07 — Tên + text info đa ngôn ngữ (vi/en); icon info dùng chung → `combo_translations` + `combo_info_translations`, icon trên `combo_infos`
- 2026-06-07 — API auth JWT bắt buộc
- 2026-06-07 — Combo có cover (đa ngôn ngữ)
- 2026-06-07 — Admin full CRUD
- 2026-06-07 — 2–7 program/combo, thứ tự pivot `sort`
- 2026-06-07 — Detail API: full program detail như `GET /programs/{program}`
- 2026-06-07 — Không `is_active`; sort list = combo có program favorite lên trước
- 2026-06-07 — Combo chỉ nhóm nội dung, không liên quan subscription
- 2026-06-07 — List API: id, name, cover, infos, program_count
- 2026-06-07 — Text info max 100 ký tự
- 2026-06-07 — List không phân trang

## Liên quan

- `docs/specs/web/program-list/spec.md`, `docs/specs/web/program-detail/spec.md`
- `docs/specs/admin/banner-management/spec.md`
- `app/Share/Models/Program.php`
