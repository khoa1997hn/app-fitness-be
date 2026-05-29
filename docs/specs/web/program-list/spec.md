# Spec: API list Program cho màn Home (app)

## Bối cảnh

App native (end-user) cần API để render màn **Home** (xem mockup [`figma/home.png`](../figma/home.png)).
Hiện DB chưa có model/table cho `programs`, `lessons`, `videos` (xem `docs/project-overview.md` — mục "CHƯA có").
Spec này tạo nền tảng DB cho Program/Lesson/Video **và** endpoint Web V1 để app "get all" program hiển thị ở Home.

> Persona: end-user (đã đăng nhập) mở app, xem danh sách program ở Home.

## Ghi chú gốc từ user (raw, không xóa)

- API list program thông tin cơ bản để hiển thị list ở home, ở list các bài học (có filter) không cần phân trang. Nếu trả về list video thì chỉ trả về tên video (tên bài học), và các thông tin khác không bao gồm link xem.
- Program: mô tả (textarea); số phút (thời gian để học hết program); goal (array các đoạn text — lợi ích khi tập program này).
- Bài học (lesson) gồm: mô tả, tên bài, thời gian video.
- Phần video của bài học tách table riêng. Trước mắt 1 bài học có 1 video, nhưng sau có thể nhiều video. Tách ra cũng để lưu thông tin upload, dung lượng, loại file, sau xử lý convert video.
- Phần này trước mắt dùng để app get all hiển thị màn Home như ảnh `figma/home.png`.

## Phạm vi

### In-scope
- Tạo model + migration: `Program` (+ `program_translations`), `ProgramGoal` (+ `program_goal_translations`), `Lesson` (+ `lesson_translations`), `Video` (+ `video_translations`).
- Enum: `LessonType` (level/special/signature), `Level` (beginner/intermediate/advanced).
- Endpoint Web V1 `GET /api/v1/programs` (auth Bearer JWT) — list program thông tin cơ bản cho Home, KHÔNG phân trang.
- Seeder dữ liệu mẫu: 7 program + vài lesson/video mỗi program, cả `vi`/`en`.

### Out-of-scope
- Admin CRUD program/lesson/video (phase sau).
- Upload / convert video thực tế (chỉ định nghĩa cột, seeder set giá trị mẫu).
- Logic gate access video theo subscription plan.
- Endpoint chi tiết program / list lesson theo level / streaming video (phase sau).

## Nghiệp vụ

- Mỗi **Program** (bộ môn: Yoga, Pilates...) có nhiều **Lesson** (bài học).
- Mỗi **Lesson** hiện có đúng 1 **Video** (tách table riêng để mở rộng nhiều video + lưu metadata file).
- Mỗi **Lesson** thuộc đúng 1 `type` (level/special/signature); khi `type=level` thì có thêm `level` (beginner/intermediate/advanced).
- Màn Home get all program, render card: cover, name, rating, duration (phút), số courses.
- KHÔNG phân trang. Sort theo `sort` (asc) rồi `id` (desc).
- Response KHÔNG trả link xem video.
- Đa ngôn ngữ CẢ content (trừ `rating`): name, description, cover, sort, goal, lesson name/description, video file + duration đều theo locale (`x-locale`).

## Mô hình dữ liệu (chốt)

> Quy ước file: dùng pattern File hiện có (`app/Share/Attributes/File.php` + `FileCast` + cột `jsonb`) như Banner — đã đủ rõ (path/name/extension/size + url). KHÔNG đổi rule file.

### `programs` (shared)
- `id`
- `rating` decimal(2,1) nullable — admin nhập sau, chưa có logic rating thật.
- `timestamps`

### `program_translations`
- `id`, `program_id` FK→programs cascadeOnDelete
- `locale` index, unique(`program_id`,`locale`)
- `name` string
- `description` text nullable
- `cover` jsonb nullable — File (path/name/extension/size), cast `FileCast`
- `sort` integer default 0
- KHÔNG timestamps

### `program_goals` (shared)
- `id`, `program_id` FK→programs cascadeOnDelete
- `sort` integer default 0
- `timestamps`

### `program_goal_translations`
- `id`, `program_goal_id` FK→program_goals cascadeOnDelete
- `locale` index, unique(`program_goal_id`,`locale`)
- `content` text
- KHÔNG timestamps

### `lessons` (shared)
- `id`, `program_id` FK→programs cascadeOnDelete
- `type` string (enum `LessonType`) NOT NULL
- `level` string (enum `Level`) nullable — chỉ set khi `type=level`
- `timestamps`

### `lesson_translations`
- `id`, `lesson_id` FK→lessons cascadeOnDelete
- `locale` index, unique(`lesson_id`,`locale`)
- `name` string
- `description` text nullable
- KHÔNG timestamps

### `videos` (shared)
- `id`, `lesson_id` FK→lessons cascadeOnDelete
- `timestamps`

### `video_translations` (file + duration đa ngôn ngữ)
- `id`, `video_id` FK→videos cascadeOnDelete
- `locale` index, unique(`video_id`,`locale`)
- `file` jsonb — File (path/name/extension/size), cast `FileCast`
- `duration_seconds` unsignedInteger
- KHÔNG timestamps

### Computed (không lưu, không cache snapshot)
- `duration_minutes` của program = `round(sum(video.duration_seconds theo locale) / 60)`.
- `course_count` của program = số lessons (`withCount`).

## API Design

### GET /api/v1/programs
- **Auth**: required (Bearer JWT, `auth:api`).
- **Headers**: `x-locale` (optional, vi|en).
- **Query**: none. No pagination.
- **Sort**: `sort` asc (theo translation), `id` desc.
- **Response 200**:
  ```json
  {
    "success": true,
    "message": "Success",
    "data": [
      {
        "id": 1,
        "name": "Pilates",
        "description": "…",
        "cover": {
          "path": "programs/cover/abc.jpg",
          "name": "pilates.jpg",
          "extension": "jpg",
          "size": 12345,
          "url": "http://localhost/storage/programs/cover/abc.jpg"
        },
        "rating": 4.9,
        "duration_minutes": 30,
        "course_count": 12,
        "goals": ["Define physique", "Improve flexibility"]
      }
    ]
  }
  ```
  - `cover` = null nếu chưa có file.
- **Errors**: 401 (thiếu/sai token), 500.

## Input / Output

### Input
- Header `Authorization: Bearer <JWT>` (bắt buộc, `auth:api`).
- Header `x-locale` (optional, default `config('app.locale')`).
- Không filter param. Không pagination.

### Output (mỗi program)
- `id` int
- `name` string (theo locale)
- `description` string|null (theo locale)
- `cover` object|null `{ path, name, extension, size, url }` (theo locale) — File serialize
- `rating` number|null
- `duration_minutes` int (computed)
- `course_count` int (computed)
- `goals` string[] (theo locale, sort tăng dần)

## Acceptance criteria

- [ ] `GET /api/v1/programs` không token → 401.
- [ ] Có token hợp lệ → 200 + danh sách program theo locale.
- [ ] Response KHÔNG chứa link/đường dẫn xem video.
- [ ] Không phân trang (trả full list).
- [ ] Sort theo `sort` asc, `id` desc.
- [ ] `duration_minutes`, `course_count` tính đúng từ lessons/videos.
- [ ] Đổi `x-locale` vi↔en → name/description/cover/goals đổi theo.

## Quyết định

> Ghi quyết định user đã trả lời qua ASK.

- 2026-05-29 — Endpoint trả về gì? → Chỉ list program (thông tin cơ bản). Lesson/video endpoint khác phase sau.
- 2026-05-29 — Auth? → Bắt buộc Bearer JWT (`auth:api`).
- 2026-05-29 — Field card Home? → Theo figma (cover + name + duration + rating + course_count). Bổ sung: `rating` là field lưu cứng trên `programs`, admin nhập sau, chưa làm logic rating thật.
- 2026-05-29 — Filter? → Không filter. Thêm field `sort` (default 0), sort theo `sort` asc rồi `id` desc.
- 2026-05-29 — Tạo table nào? → Cả Program + Lesson + Video (cần Lesson để count số phút & số lesson/program).
- 2026-05-29 — Field đa ngôn ngữ? → Tất cả content đa ngôn ngữ TRỪ `rating`.
- 2026-05-29 — `duration_minutes`? → Tính động (sum video duration). KHÔNG cache snapshot (KHÔNG OVERKILL; thêm sau nếu thành vấn đề perf).
- 2026-05-29 — `course_count`? → Tính động (count lessons).
- 2026-05-29 — Video model? → Video = lesson_id + file + `duration_seconds` (int, giây). Program duration_minutes = round(sum/60).
- 2026-05-29 — Lưu thông tin file? → (Đã đảo quyết định) Ban đầu định tách cột rõ ràng `*_path/*_extension/...`. Sau khi xem `app/Share/Attributes/File.php` (Banner đang dùng) → DÙNG pattern File jsonb + `FileCast` hiện có (path/name/extension/size + url đủ rõ). KHÔNG đổi rule file (`10`/`12` giữ nguyên).
- 2026-05-29 — Đa ngôn ngữ Lesson & Video? → Cả hai. Lesson name/description đa ngôn ngữ; video file + duration đa ngôn ngữ (video khác nhau vi/en).
- 2026-05-29 — Level/type Lesson? → Mô hình ngay: 2 enum `type` (level/special/signature) NOT NULL + `level` (beginner/intermediate/advanced) nullable.
- 2026-05-29 — Lưu `goal`? → Bảng riêng `program_goals` + translation tách thành `program_goal_translations` (như model translatable khác).
- 2026-05-29 — Cột file? → Bộ cột gồm cả `*_original_name`.
- 2026-05-29 — Seeder? → Seed 7 program + vài lesson/video mỗi program, cả vi/en.

## Liên quan

- Mockup: [`figma/home.png`](../figma/home.png)
- Project overview: `docs/project-overview.md`
- Mẫu translatable: `app/Share/Models/Banner.php`, `BannerController`
