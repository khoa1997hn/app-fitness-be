# Spec: S3 media paths cho FakeDatabaseSeeder

## Bối cảnh

`FakeDatabaseSeeder` seed banner, program, lesson (kèm video) cho dev/test. Các field file (`cover`, `thumbnail`, `image`, `file`) lưu **S3 object key** trong DB — cần object **đã tồn tại trên bucket** thì presigned GET / admin preview / app play mới hoạt động.

Hiện trạng:
- `ProgramsSeeder`: cover dùng `program/cover/sample.jpg` (placeholder); thumbnail + video có 1 path cố định.
- `BannerFactory`: `path/to/image.jpg` (placeholder).

## Phạm vi

### In-scope
- Pool **object key S3** (2–3 path / loại, user bổ sung sau) trong const từng seeder/factory.
- Random chọn path từ pool mỗi bản ghi seed (banner, program cover, lesson thumbnail, lesson video).
- Cùng path cho locale `vi` + `en` (ảnh/video giống nhau).
- `duration_seconds` video: cố định **600**.
- Seeder liên quan: `BannersSeeder` → `BannerFactory`; `ProgramsSeeder` (cover, thumbnail, video).
- Metadata file (`name`, `extension`, `size`) derive từ path; `size` video giữ giá trị mặc định hợp lý nếu chưa biết.

### Out-of-scope
- Upload file lên S3 trong seeder.
- Thay đổi `DatabaseSeeder` (chỉ fake data).
- Web API / Admin CRUD.

## Nghiệp vụ

### Loại file & prefix (theo `config/app_file.php`)
| Loại | Prefix | Seeder |
|------|--------|--------|
| Banner image | `banner/image/` | `BannerFactory` |
| Program cover | `program/cover/` | `ProgramsSeeder` |
| Lesson thumbnail | `lesson/thumbnail/` | `ProgramsSeeder` |
| Lesson video | `lesson/video/` | `ProgramsSeeder` |

### Luồng
1. User upload file lên S3 (thủ công / admin) → lấy object key.
2. User paste 2–3 key / loại vào const trong code (xem comment `TODO(user)`).
3. Chạy `php artisan db:seed --class=FakeDatabaseSeeder` → mỗi bản ghi random 1 key từ pool tương ứng.

### Pool rỗng / chưa điền
- Giữ **ít nhất 1 fallback path** trong const (có comment TODO) để seeder không crash; ảnh/video chỉ playable sau khi user thay bằng key thật.

## Input / Output

### Input (user cung cấp sau)
- Mảng object key S3, format: `banner/image/xxx.jpg`, `program/cover/yyy.jpg`, `lesson/thumbnail/zzz.jpg`, `lesson/video/aaa.mp4`.
- 2–3 key / loại.

### Output (DB)
- JSON `File` trong translation: `{ path, name, extension, size }`.

## Acceptance criteria

- [ ] `BannerFactory` random `image` từ pool `BANNER_IMAGE_PATHS`; vi + en cùng path.
- [ ] `ProgramsSeeder` random `cover` mỗi program từ pool.
- [ ] `ProgramsSeeder` random `thumbnail` mỗi lesson từ pool.
- [ ] `ProgramsSeeder` random `file` (video) mỗi lesson từ pool; `duration_seconds` = 600.
- [ ] Const có comment hướng dẫn user paste object key thật.
- [ ] `FakeDatabaseSeeder` chạy không lỗi khi pool có ≥ 1 entry / loại.

## Quyết định

- **2026-06-06** — Format path → **object key S3** trực tiếp (không lưu URL).
- **2026-06-06** — Gán path → **random từ pool** mỗi bản ghi.
- **2026-06-06** — Locale ảnh → **cùng path** vi + en.
- **2026-06-06** — Lưu pool → **const trong từng Seeder/Factory** (không file config chung).
- **2026-06-06** — Số path / loại → **2–3** (user bổ sung sau).
- **2026-06-06** — `duration_seconds` → **cố định 600**.
- **2026-06-06** — Implement → **structure + pool TODO trước**; user paste key thật sau.

## Update 2026-06-06 — Object key thật (user cung cấp)

### Pool đã điền

**Banner** (`BannerFactory::BANNER_IMAGE_PATHS`) — 3 key:
- `banner/image/dSflmHXlFnqBJO84yduaJ3Y2S4l0ccNlp0zfnYG8.webp`
- `banner/image/Raffh0eAMM85t2etJNTNRS0sPXGnDb7oBaAmtoN0.webp`
- `banner/image/EHt7KM5lB88MFlFNncOjyCqmPr9WwfGub2YOJlj5.jpg`

**Program cover** (`ProgramsSeeder::PROGRAM_COVER_PATHS`) — 2 key:
- `program/cover/LYB0r26N4mea4fhy6ABJfRPsBbp0TA1kYjh8tolp.jpg`
- `program/cover/K6PbHcUZnjzdJ0xmQDvym5YiFpq4Se0xMnmAjkeD.jpg`

**Lesson thumbnail** (`ProgramsSeeder::LESSON_THUMBNAIL_PATHS`) — 2 key:
- `lesson/thumbnail/JBZubf0yvag8uR74fZTovDt1wk3lCVC4zUirQDW1.jpg`
- `lesson/thumbnail/MMhxN3JMiDsYcsMZ8oGF0pEN0QQzhBFUQ3afIOtk.jpg`

**Lesson video** (`ProgramsSeeder::LESSON_VIDEO_PATHS`) — 1 key:
- `lesson/video/5LVjU1yCw4OvN976I5DIMjQ6dFzSm46QaeLUT3qU.mp4`

### Còn thiếu (optional)
- Video: chỉ 1 key — mọi lesson dùng cùng file (đủ dev); thêm key thứ 2–3 nếu muốn đa dạng khi random.
- `size` (bytes): seeder dùng default derive — không ảnh hưởng presigned GET/play.

### Quyết định
- **2026-06-06** — User cung cấp đủ key banner (3), cover (2), thumbnail (2); video (1) chấp nhận tạm.

## Liên quan

- `database/seeders/FakeDatabaseSeeder.php`
- `docs/rules/07-seeders.md`, `docs/rules/12-file-upload.md`
- `config/app_file.php`
