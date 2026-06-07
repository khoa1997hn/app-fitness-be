# Plan: Combo — Admin CRUD + API list/detail

## Tóm tắt

Tạo bảng combo + translation + pivot program + info items; Admin CRUD Blade (theo pattern Banner); Web API list/detail với trait tái dùng map program từ `ProgramController`.

## Phụ thuộc

- `Program` model đã có.
- `FileUploadService`, `AdminS3Upload` JS.
- `VideoWatchProgressService` cho progress trong program detail.

## Các pha

### Pha 1 — DB + Model + FileType
- Migration: combos, combo_translations, combo_program, combo_infos, combo_info_translations
- Models: Combo, ComboTranslation, ComboInfo, ComboInfoTranslation
- FileType `ComboCover`, `ComboInfoIcon` + `config/app_file.php`

### Pha 2 — Admin
- StoreComboRequest, UpdateComboRequest
- ComboController (CRUD + sync programs/infos)
- Views: index, create, edit, _form
- Route + sidebar menu

### Pha 3 — Web API
- Trait `MapsProgramForApi` (extract từ ProgramController)
- ComboController API: index, show
- Routes `api.php`

### Pha 4 — OpenAPI + docs
- Swagger attributes ComboController
- `l5-swagger:generate`
- Update `docs/project-overview.md`

## Rủi ro

- Detail combo N+1 nếu nhiều program — chấp nhận (max 7 program).
- Sort favorite-first cần query pivot `program_favorites`.

## Verify thủ công

1. Admin tạo combo 2 program + 2 info → lưu OK
2. Admin sửa/xóa combo
3. `GET /combos` không token → 401
4. `GET /combos` có token → list đúng field, combo favorite-first
5. `GET /combos/{id}` → programs full detail + lessons grouped
6. Đổi `x-locale` → name/text đổi
