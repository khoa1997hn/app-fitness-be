# Plan: Admin quản lý Program (bộ môn) & Lesson (bài học)

> Kế hoạch triển khai dựa trên `spec.md` cùng folder (mục "Quyết định ĐÃ CHỐT" là nguồn chính).

## Tóm tắt

Thêm Admin CRUD cho Program (chỉ edit + delete) và Lesson (CRUD đầy đủ, nested trong program), kèm upload/xem 1 video/lesson qua presigned S3 đã có sẵn. Code controller-first, Blade + Dashcode tiếng Việt, lặp động theo locale từ config. KHÔNG migration mới, KHÔNG package mới — chỉ bổ sung relation/enum label trên model có sẵn và build UI.

## Phụ thuộc

- Migration mới: KHÔNG (toàn bộ table đã tồn tại).
- Model mới: KHÔNG (chỉ bổ sung relation vào `Program`, `Lesson`).
- Enum: bổ sung label tiếng Việt cho `LessonType`, `Level` (đã tồn tại, cần override getDescription).
- Endpoint mới (admin, guard `auth:admin`):
  - `Route::resource('programs')` only `[index, edit, update, destroy]`
  - `Route::resource('programs.lessons')` only `[create, store, edit, update, destroy]`
- Endpoint sửa: KHÔNG.
- View Admin mới: `programs/index`, `programs/edit`, `lessons/create`, `lessons/edit`.
- View Admin sửa: `components/sidebar` (thêm menu "Bộ môn").
- Service mới: KHÔNG. Logic chỉ dùng 1 chỗ, tính tổng duration đặt làm accessor/loop trên Model hoặc trong Controller (controller-first, `docs/rules/01-architecture.md`).
- Package/composer mới: KHÔNG.
- Reuse (KHÔNG đụng): route `admin.files.presigned-upload`, `public/js/admin/s3-presigned-upload.js` (`window.AdminS3Upload.upload(file, type)`), `FileType` (ProgramCover/LessonVideo/LessonThumbnail), `ResponseAPI`.

## Các pha

### Pha 1 — Nền tảng Model + Enum
- Mục tiêu: chuẩn bị quan hệ và label trước khi controller/view dùng tới.
  - `Program`: thêm relation `favorites()` = `belongsToMany(User, 'program_favorites')`; cung cấp cách tính tổng thời lượng (program → lessons → videos → video_translations.duration_seconds). Vì `duration_seconds` nằm ở bảng translation nên KHÔNG dùng `withSum` thẳng — eager load `lessons.videos.translations` rồi cộng dồn (accessor hoặc tính trong controller, chọn cách đơn giản nhất, không overkill).
  - `Lesson`: thêm relation `favorites()` = `belongsToMany(User, 'lesson_favorites')`.
  - `LessonType`, `Level`: override `getDescription()` map sang nhãn tiếng Việt (BenSampo).
- Files đụng:
  - `app/Share/Models/Program.php`
  - `app/Share/Models/Lesson.php`
  - `app/Share/Enums/LessonType.php`
  - `app/Share/Enums/Level.php`
- Dependency: không.
- Rule liên quan: `docs/rules/11-enum.md` (label getDescription, không `->value` ở response), `docs/rules/10-database-design.md` (không thêm field phòng xa), `docs/rules/14-translatable.md` (pattern 2 bảng translation), `docs/rules/02-code-quality.md`.
- Verify thủ công:
  - `php artisan tinker`: `App\Share\Models\Program::with('lessons.videos.translations')->first()` chạy không lỗi; gọi relation `favorites` ra collection User.
  - `App\Share\Enums\LessonType::level()->description` (hoặc tương đương) trả nhãn tiếng Việt; tương tự `Level`.

### Pha 2 — Routes + Requests + Controllers
- Mục tiêu: dựng backend xử lý cho Program (edit/update/destroy + index list) và Lesson (CRUD nested).
  - Routes: thêm 2 `Route::resource` trong group `auth:admin`, đúng `only(...)` như trên.
  - `UpdateProgramRequest`: validate `name`/`cover` required ở locale `vi`, `description`/`sort` optional theo từng locale; `rating` nullable decimal 0.0–5.0. Lặp rule động theo `config('translatable.locales')`, chỉ ép required cho `vi`.
  - `StoreLessonRequest` + `UpdateLessonRequest`: `type` required (enum), `level` required khi `type = level` và phải null khi `special`/`signature`; `day` integer min 1 (KHÔNG unique); `name`/`thumbnail` required locale `vi`, `description` optional; video `file` key S3 + `duration_seconds` required integer (nhập tay). Lặp động theo locale.
  - `ProgramController`: `index` (list sort `id`, KHÔNG paginate/filter, kèm số favorite + số lesson + tổng thời lượng mỗi dòng); `edit` (load program + lesson list CÓ paginate, mỗi lesson kèm số favorite); `update`; `destroy` (xóa program, cascade theo FK).
  - `LessonController`: `create`/`store`/`edit`/`update`/`destroy`. Lưu video: BE chỉ lưu key S3 vào `video_translations.file` (FileCast). Redirect sau `store`/`update`/`destroy` → trang edit program.
- Files đụng:
  - `routes/admin.php`
  - `app/Admin/Http/Requests/UpdateProgramRequest.php`
  - `app/Admin/Http/Requests/StoreLessonRequest.php`
  - `app/Admin/Http/Requests/UpdateLessonRequest.php`
  - `app/Admin/Http/Controllers/ProgramController.php`
  - `app/Admin/Http/Controllers/LessonController.php`
- Dependency: Pha 1 (relation favorites, tổng duration, enum label).
- Rule liên quan: `docs/rules/03-project-structure.md` (đặt đúng `app/Admin/`), `docs/rules/01-architecture.md` (controller-first, FormRequest cho form phức tạp), `docs/rules/02-code-quality.md` (`query()`, `\Throwable`, enum-as-string), `docs/rules/12-file-upload.md` (lưu key S3, FileCast), `docs/rules/14-translatable.md` (ghi nhiều locale), `docs/rules/11-enum.md`.
- Verify thủ công:
  - `php artisan route:list --path=admin/programs` thấy đủ 7 route (programs: index/edit/update/destroy; lessons: create/store/edit/update/destroy).
  - Submit form edit program sai (bỏ trống `name` vi, `rating` = 6) → trả lỗi validation tiếng Việt.
  - Submit lesson `type = level` không chọn `level` → lỗi; `type = special` mà gửi `level` → bị ép null/lỗi theo rule chốt.

### Pha 3 — Views Blade (Dashcode, tiếng Việt)
- Mục tiêu: dựng UI khớp controller/route đã có.
  - `programs/index`: table sort `id`, cột cover, tên, rating, số favorite, số lesson, tổng thời lượng + action sửa/xóa. KHÔNG filter/paginate.
  - `programs/edit`: form sửa program (lặp tab/khối theo locale từ config; required đánh dấu ở `vi`), cover bắt buộc (dùng `window.AdminS3Upload.upload(file, 'program_cover')`), rating nhập tay; bên dưới là table lesson CÓ paginate, mỗi dòng kèm số favorite + nút sửa/xóa lesson + nút thêm lesson.
  - `lessons/create`: form lesson (type/level/day + field translatable), upload video qua presigned + ô nhập `duration_seconds`. Ô `level` ẩn/hiện theo `type`.
  - `lessons/edit`: như create + player HTML5 nhúng sẵn video qua `$video->file->url()` (presigned GET).
- Files đụng:
  - `resources/views/admin/programs/index.blade.php`
  - `resources/views/admin/programs/edit.blade.php`
  - `resources/views/admin/lessons/create.blade.php`
  - `resources/views/admin/lessons/edit.blade.php`
- Dependency: Pha 2 (route name + data controller truyền sang).
- Rule liên quan: `docs/rules/05-admin-blade.md` (Blade + Dashcode + tiếng Việt), `docs/rules/12-file-upload.md` (reuse `AdminS3Upload`, presigned GET hiển thị), `docs/rules/14-translatable.md` (lặp locale từ config, không hard-code vi/en rải rác).
- Verify thủ công:
  - Vào `/admin/programs` thấy danh sách với đủ 6 cột thống kê.
  - Mở edit 1 program → sửa và lưu thành công, cả locale `vi` lẫn locale khác (nếu nhập).
  - Tạo lesson mới có upload video → sau submit quay lại trang edit program, lesson xuất hiện trong table.
  - Mở edit lesson → player phát được video.

### Pha 4 — Wiring menu + dọn liên kết
- Mục tiêu: thêm lối vào tính năng từ sidebar.
  - `components/sidebar`: thêm mục menu "Bộ môn" trỏ `admin.programs.index`.
- Files đụng:
  - `resources/views/admin/components/sidebar.blade.php`
- Dependency: Pha 2 (route name), Pha 3 (view tồn tại).
- Rule liên quan: `docs/rules/05-admin-blade.md`.
- Verify thủ công: đăng nhập admin, thấy menu "Bộ môn", click vào ra đúng list program.

### Pha 5 — Verify tổng & finalize
- Mục tiêu: chạy lại toàn bộ acceptance criteria của spec.
- Files đụng: không (chỉ kiểm thử + pint/migration check ở finalizer).
- Dependency: Pha 1–4.
- Verify thủ công (theo Acceptance criteria spec):
  - List program không phân trang, sort id, đủ cột số favorite/số lesson/tổng thời lượng.
  - Edit program lưu được cả vi (+locale khác nếu nhập); xóa program cascade lesson/video/favorite.
  - Lesson tạo/sửa/xóa OK; redirect đúng về edit program.
  - Upload 1 video presigned PUT → DB chỉ lưu key S3; xem lại video qua presigned GET trong player.
  - Mọi label/message tiếng Việt.

## Rủi ro

- Tổng thời lượng program: `duration_seconds` nằm ở bảng translation (`video_translations`) nên không `withSum` trực tiếp được → cần eager load `lessons.videos.translations` và cộng dồn; nếu nhiều program/lesson có thể N+1. Phòng: eager load đủ ở `index`, tính bằng vòng lặp đơn giản (chưa cần cache, không overkill).
- Ràng buộc `type` ↔ `level`: phải đảm bảo cả ở Request (validation) lẫn ở form Blade (ẩn/hiện + reset value). Phòng: ép `level = null` ở server khi type không phải `level`, không tin client.
- Lặp động theo locale: dễ lỡ hard-code `vi`/`en` ở Request hoặc Blade. Phòng: luôn đọc `config('translatable.locales')`, chỉ ép required cho locale mặc định `vi`.
- Video 1/lesson: DB là hasMany nhưng nghiệp vụ giới hạn 1 → khi update phải cập nhật đúng record video hiện có thay vì tạo trùng. Phòng: lấy video đầu tiên của lesson để update, tạo mới nếu chưa có.

## Verify thủ công (tổng hợp lệnh)

- `php artisan route:list --path=admin/programs` — kiểm tra 7 route.
- `php artisan tinker` — kiểm tra relation `favorites`, tính tổng duration, enum label tiếng Việt.
- Thao tác UI: list → edit program → CRUD lesson → upload + xem video → xóa program (cascade).

## Update 2026-06-02 — Fix lỗi upload PUT S3 (CORS)

**Root cause:** bucket `local-bucket` (s3.cloudfly.vn) chưa có CORS → browser preflight PUT bị chặn (`net::ERR_FAILED`). Không phải lỗi code.

**Pha U1 — Artisan command set CORS**
- Mục tiêu: tạo `php artisan s3:put-cors` set CORS cho bucket qua AWS SDK (`PutBucketCors`).
- File: `app/Share/Console/Commands/PutBucketCorsCommand.php` (mới); đăng ký command trong `bootstrap/app.php` (`->withCommands([...])`) vì project giới hạn `app/` chỉ có Admin/Web/Share (rule 03), không dùng `app/Console/Commands` mặc định.
- Lấy S3 client qua `Storage::disk('s3')->getClient()`; bucket từ `config('filesystems.disks.s3.bucket')` (rule 09: không `env()` trong logic).
- CORS: AllowedOrigins `['*']` (dev), Methods `[PUT,GET,HEAD]`, Headers `['*']`, ExposeHeaders `['ETag']`, MaxAgeSeconds 3600.
- Verify: chạy qua Sail `sail exec --user sail laravel.test php artisan s3:put-cors`; sau đó probe lại `OPTIONS` bucket thấy trả CORS headers; upload cover trên UI thành công.
- Rule liên quan: 06-docker-sail (Sail), 09-magic-and-env (config), 12-file-upload (S3 presigned), 02-code-quality (\Throwable).


## Update 2026-06-02 (f) — UI nâng cấp view

### Ảnh hưởng
- `resources/views/admin/programs/index.blade.php` — rating → sao, favorites → trái tim, duration → H:i:s
- `resources/views/admin/programs/edit.blade.php` — lesson favorites → trái tim, Ngày → badge

### Verify
- [ ] List program: cột rating hiển thị sao vàng + số
- [ ] List program: cột yêu thích hiển thị trái tim đỏ + số
- [ ] List program: cột thời lượng hiển thị H:i:s (ví dụ 1:05:30)
- [ ] List lesson trong edit program: cột yêu thích hiển thị trái tim đỏ + số
- [ ] List lesson trong edit program: cột Ngày hiển thị badge màu

## Update 2026-06-06

- Cột **Tên** list program + list lesson (trong edit program) → link tới trang sửa tương ứng.
- Pattern: `text-primary-500 hover:underline font-medium` (tham chiếu `users/index.blade.php`).

### Verify
- [ ] Bấm tên program → mở `/admin/programs/{id}/edit`
- [ ] Bấm tên lesson → mở trang edit lesson
- [ ] Menu ba chấm Sửa/Xóa vẫn hoạt động
