# Task: Admin quản lý Program (bộ môn) & Lesson (bài học)

> Checklist atomic. Mỗi task ≤ 1 đơn vị code (1 file / 1 method / 1 migration). Tick khi xong.

## Pha pre-design

- [ ] solution-reviewer: review proposal solution của user (hoặc note "no proposal")
- [ ] api-designer: feature là Admin Blade (không endpoint Web V1 JSON) → không có section "API Design", note "n/a"

## Pha 1 — Nền tảng Model + Enum

- [x] Thêm relation `favorites()` = `belongsToMany(User::class, 'program_favorites')` vào `app/Share/Models/Program.php`
- [x] Thêm cách tính tổng thời lượng program (eager load `lessons.videos.translations` rồi cộng dồn `duration_seconds`, accessor đơn giản hoặc helper) vào `app/Share/Models/Program.php`
- [x] Thêm relation `favorites()` = `belongsToMany(User::class, 'lesson_favorites')` vào `app/Share/Models/Lesson.php`
- [x] Override `getDescription()` map nhãn tiếng Việt cho các case trong `app/Share/Enums/LessonType.php`
- [x] Override `getDescription()` map nhãn tiếng Việt cho các case trong `app/Share/Enums/Level.php`

## Pha 2 — Routes + Requests + Controllers

- [x] Đăng ký `Route::resource('programs')->only(['index','edit','update','destroy'])` trong group `auth:admin` tại `routes/admin.php`
- [x] Đăng ký `Route::resource('programs.lessons')->only(['create','store','edit','update','destroy'])` trong group `auth:admin` tại `routes/admin.php`
- [x] Tạo `app/Admin/Http/Requests/UpdateProgramRequest.php`: lặp rule động theo `config('translatable.locales')`, ép `name`/`cover` required chỉ ở `vi`, `description`/`sort` optional theo locale, `rating` nullable decimal 0.0–5.0
- [x] Tạo `app/Admin/Http/Requests/StoreLessonRequest.php`: `type` required (enum), `level` required khi `type=level` & null khi `special`/`signature`, `day` integer min 1 (không unique), `name`/`thumbnail` required ở `vi`, `description` optional, video `file` key S3 + `duration_seconds` required integer; lặp động theo locale
- [x] Tạo `app/Admin/Http/Requests/UpdateLessonRequest.php`: cùng rule với StoreLessonRequest (lặp động theo locale, type↔level, day min1 không unique, duration_seconds required)
- [x] Tạo `app/Admin/Http/Controllers/ProgramController.php` — method `index`: list sort `id`, KHÔNG paginate/filter, withCount `favorites`+`lessons` + tổng thời lượng mỗi dòng (eager load đủ tránh N+1)
- [x] Thêm method `edit` vào `ProgramController`: load program + danh sách lesson CÓ paginate, mỗi lesson kèm số favorite
- [x] Thêm method `update` vào `ProgramController`: lặp ghi các locale + cập nhật `rating`
- [x] Thêm method `destroy` vào `ProgramController`: xóa program (cascade theo FK)
- [x] Tạo `app/Admin/Http/Controllers/LessonController.php` — method `create`: trả form tạo lesson cho 1 program
- [x] Thêm method `store` vào `LessonController`: tạo lesson + lưu 1 video key S3 vào `video_translations.file` + `duration_seconds`, redirect về edit program
- [x] Thêm method `edit` vào `LessonController`: load lesson + video hiện có cho form sửa
- [x] Thêm method `update` vào `LessonController`: cập nhật lesson + cập nhật đúng record video hiện có (tạo nếu chưa có), ép `level=null` khi type≠level, redirect về edit program
- [x] Thêm method `destroy` vào `LessonController`: xóa lesson, redirect về edit program

## Pha 3 — Views Blade (Dashcode, tiếng Việt)

- [x] Tạo `resources/views/admin/programs/index.blade.php`: table sort `id`, cột cover/tên/rating/số favorite/số lesson/tổng thời lượng + action sửa/xóa, KHÔNG filter/paginate
- [x] Tạo `resources/views/admin/programs/edit.blade.php`: form đa locale (lặp theo config, required đánh dấu ở `vi`) + upload cover qua `window.AdminS3Upload.upload(file,'program_cover')` + rating nhập tay + table lesson CÓ paginate (cột số favorite + action edit/xóa) + nút thêm lesson
- [x] Tạo `resources/views/admin/lessons/create.blade.php`: dropdown type/level + JS ẩn/hiện ô level khi `type≠level`, ô day, name/description/thumbnail đa locale, upload video presigned + ô nhập `duration_seconds` (form chung tách `admin/lessons/_form.blade.php`)
- [x] Tạo `resources/views/admin/lessons/edit.blade.php`: như create + hiển thị số favorite + player HTML5 nhúng `$video->file->url()` (presigned GET)

## Pha 4 — Wiring menu

- [x] Thêm mục menu "Bộ môn" trỏ `admin.programs.index` vào `resources/views/admin/components/sidebar.blade.php`

## Pha 5 — Verify acceptance criteria

- [ ] `php artisan route:list --path=admin/programs` thấy đủ 7 route (programs index/edit/update/destroy; lessons create/store/edit/update/destroy)
- [ ] List program không paginate, sort id, đủ cột số favorite/số lesson/tổng thời lượng
- [ ] Edit program lưu được cả `vi` (+locale khác nếu nhập); xóa program cascade lesson/video/favorite
- [ ] Lesson tạo/sửa/xóa OK; redirect đúng về edit program; type↔level ràng buộc đúng
- [ ] Upload 1 video presigned PUT → DB chỉ lưu key S3; xem lại video qua presigned GET trong player
- [ ] Menu "Bộ môn" hiện trong sidebar, click ra đúng list program; mọi label/message tiếng Việt

## Pha review

- [ ] reviewer-rules pass
- [ ] reviewer-smell pass
- [ ] reviewer-security pass
- [x] reviewer-duplicate pass + fix

## Pha cleanup

- [ ] cleaner: file rác đã xóa
- [ ] cleaner: code rác (import/biến/method 0-reference) đã xóa
- [ ] cleaner: `.env` ↔ `.env.example` đồng bộ key
- [ ] cleaner: route / view / translation rác đã xóa hoặc đã hỏi user

## Pha docs sync

- [x] docs-syncer: `project-overview.md` đã reflect module Admin program/lesson (chuyển từ "CHƯA có" sang "ĐÃ có")
- [x] docs-syncer: stack đã reflect package mới (nếu có — feature này KHÔNG có package mới)
- [ ] docs-syncer: rules / guides / agents đã đồng bộ (user đã duyệt thay đổi interpretive)

## Pha finalize

- [ ] Chạy migration (nếu có — feature này KHÔNG có migration mới)
- [ ] Chạy `pint`
- [ ] Verify thủ công các bước trong `plan.md`
- [ ] STOP — hỏi user commit/push

## Update 2026-06-02 — Fix lỗi upload PUT S3 (CORS)

- [ ] Tạo `app/Share/Console/Commands/PutBucketCorsCommand.php` (signature `s3:put-cors`)
- [ ] Đăng ký command trong `bootstrap/app.php` (`->withCommands([...])`)
- [ ] Lấy S3 client `Storage::disk('s3')->getClient()`, gọi `putBucketCors` với policy dev (origins `*`, methods PUT/GET/HEAD)
- [ ] Chạy command qua Sail set CORS cho bucket
- [ ] Verify: probe OPTIONS bucket trả CORS headers + upload cover UI OK
- [ ] reviewer-rules + reviewer-security cho command mới
- [ ] docs-syncer: ghi chú CORS vào docs/rules/12-file-upload.md + 06-docker-sail.md
- [ ] finalizer: pint qua Sail + commit message đề xuất

## Update 2026-06-02 (f)

- [ ] 1. `programs/index.blade.php` — rating: sao vàng filled/outline theo `floor(rating)` + số
- [ ] 2. `programs/index.blade.php` — favorites: icon trái tim đỏ + số
- [ ] 3. `programs/index.blade.php` — thời lượng: format H:i:s
- [ ] 4. `programs/edit.blade.php` — lesson favorites: icon trái tim đỏ + số
- [ ] 5. `programs/edit.blade.php` — cột Ngày: badge info
