# Spec: Admin quản lý Program (bộ môn) & Lesson (bài học)

> Mọi field <góc nhọn> là placeholder. Mục đánh dấu `TODO(ask)` còn mơ hồ → question-asker xử lý, KHÔNG tự bịa.

## Bối cảnh

- Sản phẩm: backend app tập thể thao tại nhà qua video. Hai khái niệm chính: **Program** (bộ môn) và **Lesson** (bài học = 1 video trong program). Xem `docs/project-overview.md`.
- Module Program/Lesson/Video phía **Web API (end-user)** ĐÃ CÓ (model + migration + translatable + API list/detail/play/favorite/progress).
- Phần **Admin CRUD program/lesson + upload video** thuộc nhóm "CHƯA có" trong overview (line 128-129) → đây là feature mới.
- Persona dùng: nhân viên vận hành (admin web), guard `auth:admin`, UI Blade + Dashcode, label tiếng Việt.
- Pattern admin tham khảo đã có trong codebase:
  - `app/Admin/Http/Controllers/UserController.php` — list (paginate 10 + filter) + destroy + export.
  - `app/Admin/Http/Controllers/FileController.php` + trait `HandlesPresignedFileUpload` + `routes/admin.php` route `admin.files.presigned-upload` — pattern presigned PUT upload S3.
  - `resources/views/admin/users/index.blade.php` — mẫu Blade: breadcrumb, card, form filter, table, paginate.
  - Route admin: `routes/admin.php`, prefix `/admin`, name `admin.<feature>.<action>`, trong group `middleware('auth:admin')`.

## Phạm vi

### In-scope

**Program (bộ môn):**
- **List program** (`/admin/programs`) — KHÔNG phân trang, KHÔNG filter, sort theo `id`. Mỗi dòng hiển thị: cover, tên, rating, **số favorite**, **số lesson**, **tổng thời lượng** + action sửa/xóa. KHÔNG có trang detail riêng.
- **Edit program** — trang sửa thông tin program (chỉ sửa, KHÔNG tạo mới). Trang này CHỨA LUÔN table list **lesson** của program (CÓ phân trang), mỗi dòng lesson hiển thị **số favorite** + action đi tới trang edit lesson / xóa lesson + nút thêm lesson mới.
- **Delete program** — action xóa program (cascade lesson/video/favorite theo FK).

**Lesson (bài học):**
- **CRUD lesson** — tạo / sửa / xóa lesson thuộc 1 program (KHÔNG có trang detail riêng; thống kê favorite hiển thị ở cột table list trong trang edit program).
- Trang edit lesson chứa: form thông tin lesson + upload/xem 1 video.

**Video:**
- **Upload video cho lesson** — trước mắt **1 video / lesson** (DB hiện tại `videos.lesson_id` là hasMany, nhưng nghiệp vụ giai đoạn này giới hạn 1; không overkill mở rộng nhiều video).
  - Cơ chế: admin tạo **presigned PUT URL** lên S3 (qua pattern `FileController`/`HandlesPresignedFileUpload` có sẵn), client PUT file trực tiếp lên S3, sau đó BE chỉ **lưu path key S3** vào DB (field `file` của `video_translations`, kiểu `jsonb` + `FileCast`).
- **Xem video** — lấy **presigned GET S3 URL** để stream về xem trong admin.
- **Player** — player xem video cơ bản (HTML5 `<video>` hoặc tương đương), CHƯA cần phức tạp.

### Out-of-scope

- Nhiều video / 1 lesson (phase sau).
- Player nâng cao (tốc độ phát, chất lượng, subtitle, ...).
- Quản lý subscription/payment view (feature riêng, nhóm "CHƯA có" khác).
- Web API end-user (đã có, không đụng).

## Nghiệp vụ

### Cấu trúc dữ liệu hiện có (đã tồn tại trong codebase — KHÔNG bịa thêm)

- **`programs`** (`app/Share/Models/Program.php`): `id`, `rating` (decimal 2,1, nullable), timestamps.
  - Translatable → **`program_translations`**: `name`, `description` (nullable), `cover` (jsonb/File), `sort` (int, default 0). Unique `(program_id, locale)`. Locales: `vi` (default), `en`.
- **`lessons`** (`app/Share/Models/Lesson.php`): `id`, `program_id` (FK cascade), `type` (enum `LessonType`: `level` / `special` / `signature`), `level` (enum `Level`, nullable: `beginner` / `intermediate` / `advanced`), `day` (int), timestamps.
  - Translatable → **`lesson_translations`**: `name`, `description` (nullable), `thumbnail` (jsonb/File).
- **`videos`** (`app/Share/Models/Video.php`): `id`, `lesson_id` (FK cascade), timestamps.
  - Translatable → **`video_translations`**: `file` (jsonb/File), `duration_seconds` (unsignedInteger).
- **`program_favorites`**: pivot `(user_id, program_id)` unique → đếm số user thích program.
- **`lesson_favorites`**: pivot `(user_id, lesson_id)` unique → đếm số user thích lesson.
- **`subscription_program_selections`** (`SubscriptionProgramSelection`): `(subscription_id, user_id, program_id)` → user gói Basic/Plus chọn program. Dùng để đếm "số người đang purchased program". Xem TODO(ask) #3.
- **`subscriptions`**: master, có `plan` (Plan enum), `status` (SubscriptionStatus), `expires_at`, ...

### Đa ngôn ngữ (BẮT BUỘC)

- Program / Lesson / Video đều translatable (vi/en). Form admin tạo/sửa phải nhập được cả 2 locale cho các field translated (`name`, `description`, `cover`/`thumbnail`, `sort` của program; `name`, `description`, `thumbnail` của lesson; `file`, `duration_seconds` của video).
- TODO(ask) #2: form admin nhập cả 2 locale cùng lúc (vi + en trên 1 form) HAY mỗi locale 1 lần? Field nào bắt buộc cả 2 locale, field nào optional?

### Thống kê hiển thị

**Detail program — show:**
- Số lượng user thích bộ môn → `count` từ `program_favorites` (đã có data).
- Số người đang purchased bộ môn → đếm từ `subscription_program_selections`. TODO(ask) #3: định nghĩa "đang purchased" — chỉ tính subscription `status = active`/còn hạn? Gói All Access (KHÔNG tạo selection, line 123 overview) có tính vào số purchased của MỌI program không?
- Rating (`programs.rating`) — hiển thị.
- Số lesson trong program (`lessons` count) — gợi ý hiển thị.
- TODO(ask) #4: còn thống kê nào khác cần show? (ví dụ: số lượt xem video, tổng thời lượng, ... — spec gốc nói "show hết mấy cái liên quan" + "tự suggest thêm", cần chốt danh sách).

**Detail lesson — show:**
- Số lượng user yêu thích lesson → `count` từ `lesson_favorites`.
- TODO(ask) #4: thống kê khác cho lesson? (số lượt xem, % hoàn thành trung bình từ `user_video_progress`, ...).

### Luồng upload video

1. Admin chọn file video trên form lesson.
2. FE gọi `admin.files.presigned-upload` (đã có) → nhận presigned PUT URL + key.
3. FE PUT file lên S3 trực tiếp.
4. FE submit form → BE lưu key vào `video_translations.file` (FileCast), tạo/cập nhật record `videos` của lesson.
5. `duration_seconds` — TODO(ask) #5: lấy từ đâu? Admin nhập tay, hay FE đọc metadata video rồi gửi lên? (field này NOT NULL trong DB).

### Luồng xem video (admin)

- Detail lesson / detail program có nút xem video → BE sinh presigned GET URL từ key đã lưu → player HTML5 stream.

## Input / Output

### Program — create/edit

> TODO(ask) #2 + #6: chốt field bắt buộc, validation cụ thể.

- `name` (vi, en): string, required. (Required cả 2 locale? — TODO(ask) #2)
- `description` (vi, en): text, nullable.
- `cover` (vi, en): file ảnh (key S3 qua presigned). Required? — TODO(ask) #6.
- `sort` (vi, en): integer, default 0.
- `rating`: decimal(2,1), nullable. TODO(ask) #6: admin có được sửa rating tay không, hay rating do hệ thống tính (review)? Nếu hệ thống tính thì readonly.

### Lesson — create/edit

- `program_id`: bắt buộc (context của detail program).
- `type`: enum `LessonType` (`level` / `special` / `signature`), required.
- `level`: enum `Level` (`beginner` / `intermediate` / `advanced`), nullable. Ràng buộc: khi `type = level` thì `level` bắt buộc; khi `type = special`/`signature` thì `level = null`. TODO(ask) #7: xác nhận ràng buộc này.
- `day`: integer, required. TODO(ask) #7: ý nghĩa `day` (ngày thứ mấy trong lộ trình?), validation (min/max, unique trong program theo level?).
- `name` (vi, en): string, required.
- `description` (vi, en): text, nullable.
- `thumbnail` (vi, en): file ảnh (key S3).
- video: 1 video upload (key S3) + `duration_seconds`.

### Output (Blade views)

- List program: tên, cover, rating, số lesson, (có thể) số favorite/purchased.
- Detail program: thông tin + thống kê + table lesson (paginate) + action sửa/xóa lesson.
- Detail/edit lesson: form + thống kê favorite + player video.

## Acceptance criteria

- [ ] Admin xem được list program (không phân trang) tại `/admin/programs`.
- [ ] Admin mở detail 1 program thấy: thống kê (số favorite, số purchased) + table lesson có phân trang.
- [ ] Mỗi dòng lesson có action sửa / xóa.
- [ ] Admin sửa được thông tin program (cả vi + en) và lưu thành công.
- [ ] Admin xóa được program (cascade lesson/video/favorite theo FK).
- [ ] Admin tạo / sửa / xóa được lesson.
- [ ] Admin upload được 1 video cho lesson qua presigned PUT, DB chỉ lưu key S3.
- [ ] Admin xem được video qua presigned GET trong player cơ bản.
- [ ] Detail lesson hiển thị số user yêu thích.
- [ ] Mọi label/message tiếng Việt, UI theo mẫu Dashcode.

## Quyết định (ĐÃ CHỐT qua ASK — ngày 2026-06-01)

1. **KHÔNG có trang detail riêng** cho cả program lẫn lesson.
   - Thống kê (số favorite, số lesson, tổng thời lượng) → hiển thị thành **cột trong table list program**.
   - Table list **lesson** của mỗi program đặt ngay trong **trang EDIT program** (có phân trang), mỗi dòng có action đi tới **trang edit riêng của lesson** + xóa lesson.
   - Số favorite của lesson → hiển thị thành **cột trong table list lesson** (ở trang edit program).
2. **Tạo program mới: KHÔNG.** Admin chỉ **sửa + xóa** program (program seed cố định). Lesson thì CRUD đầy đủ (tạo/sửa/xóa).
3. **List program:** sort theo `id`, KHÔNG filter/search, KHÔNG phân trang.
4. **Cover program:** BẮT BUỘC (upload presigned PUT S3). **Rating:** admin nhập tay, decimal(2,1) range 0.0–5.0.
5. **Đa ngôn ngữ:** 1 form nhập nhiều locale; **field required chỉ cần bắt buộc 1 locale (vi)**, các locale khác optional. Code phải **lặp động theo danh sách locale từ config** (KHÔNG hard-code liệt kê vi/en rải rác) để sau thêm locale mới ít phải sửa code.
6. **Số người purchased:** TẠM BỎ cột này (chưa cần ở giai đoạn này) → bỏ luôn `subscription_program_selections` khỏi scope.
7. **Thống kê show:** Program → số favorite + số lesson + tổng thời lượng (sum `duration_seconds`). Lesson → số favorite.
8. **`duration_seconds`:** admin **nhập tay** (ô input số giây, required).
9. **Lesson `type` ↔ `level`:** `type = level` ⇒ `level` REQUIRED; `type = special`/`signature` ⇒ `level = null` (form ẩn ô level).
10. **Lesson `day`:** integer, min 1, ý nghĩa = ngày thứ N trong lộ trình; **KHÔNG unique** (cho phép nhiều lesson cùng day).

## Update 2026-06-02 (e) — Giữ preview ảnh/video khi validate fail + label enum để tiếng Anh

1. **Preview ảnh/video biến mất khi submit fail validate:** hidden input (path/name/...) ĐÃ giữ qua `old()` nên data không mất, NHƯNG thẻ preview `<img>`/`<video>` lấy `src` từ giá trị server (DB), không từ `old()` → reload sau validate fail thì preview trống.
   - Fix: blade resolve File **ưu tiên `old('<prefix>.path')`** (dựng `App\Share\Attributes\File::fromArray(...)` → presigned GET `url()`), fallback giá trị server. Áp cho cover (program), thumbnail + video (lesson). Preview giữ nguyên sau validate fail.
2. **Label enum để tiếng Anh:** theo yêu cầu user, giữ entry trong `lang/vi/enums.php` nhưng dùng **label tiếng Anh nguyên bản** (Level/Special/Signature, Beginner/Intermediate/Advanced) thay cho tiếng Việt.

## Update 2026-06-02 (d) — Player video phản ánh ngay sau khi upload

**Triệu chứng:** upload video (PUT S3) xong nhưng `<video>` player chưa hiển thị video vừa upload (chỉ hiện video đã lưu từ server khi load trang).

**Nguyên nhân:** giống bug preview ảnh — player chỉ render server-side khi `$video->file` đã tồn tại; JS upload chỉ set hidden input, KHÔNG cập nhật player.

**Fix:** player `<video>` luôn hiện diện (ẩn `hidden` nếu chưa có video). JS sau khi upload thành công set `<source src>` = `URL.createObjectURL(file)`, gọi `player.load()`, bỏ `hidden`. (Cùng pattern với preview ảnh cover/thumbnail.)

Rule: mở rộng note "preview media sau upload" trong `docs/rules/05-admin-blade.md` áp cho cả ảnh lẫn video.

## Update 2026-06-02 (c) — Badge dùng class Dashcode + lỗi validate dưới field + preview ảnh sau upload

3 chỉnh sửa UI (kèm rule):

1. **Badge enum trong list hiển thị đúng màu:** admin dùng CSS Dashcode **biên dịch sẵn (static, không JIT)** → các class Tailwind tùy ý (`bg-emerald-100`…) KHÔNG có trong `app.css` nên badge trước đó không lên màu. Sửa `enum-badge.blade.php` dùng đúng class Dashcode: `badge bg-<color>-500 text-<color>-500 bg-opacity-30 rounded-3xl` với palette màu có sẵn (`primary/success/warning/info/danger`), gán ổn định theo value; null → text trung tính.
2. **Lỗi validate hiển thị DƯỚI từng field** (không gom 1 cục đầu form): bỏ block `@if($errors->any())` ở đầu, thêm `@error(<field>)` ngay dưới mỗi input (gồm field đa ngôn ngữ theo từng locale). Note vào rule admin.
3. **Sau khi upload ảnh (PUT) thành công phải hiện ảnh mới:** JS set `URL.createObjectURL(file)` vào thẻ `<img>` preview của đúng locale + bỏ `hidden`. Áp dụng cho cover (program) và thumbnail (lesson).

Rule cập nhật: `docs/rules/05-admin-blade.md` (lỗi validate dưới field + badge dùng class Dashcode có sẵn) + mirror `.cursor/rules/admin-blade.mdc`.

## Update 2026-06-02 (b) — Enum label qua lang file + UI badge màu + form đa ngôn ngữ cạnh nhau

Theo yêu cầu user, 3 thay đổi (kèm cập nhật rule):

1. **Enum label KHÔNG dùng `getDescription()` match nữa** → dùng cơ chế localization gốc của BenSampo:
   - Base `app/Share/Enums/Enum.php` `implements BenSampo\Enum\Contracts\LocalizedEnum` (mọi enum đều localizable, thiếu key thì tự fallback friendly name).
   - Bỏ override `getDescription()` ở `LessonType` / `Level`.
   - Tạo `lang/vi/enums.php`: mảng `[<EnumClass>::class => [<enum value> => 'label']]`. Package tự resolve theo `app()->getLocale()` (default `vi`). Hiển thị: `$enum->description` / `Enum::asSelectArray()` như cũ.
   - File default của package là `enums.php` (số nhiều, key `enums.<FQCN>.<value>`) — đã chốt qua ASK.
2. **Label enum/status trong danh sách UI hiển thị dạng badge có màu** (trực quan hơn text trơn):
   - Partial dùng chung `resources/views/admin/components/enum-badge.blade.php` — nhận enum instance, render `description` trong span bo tròn, **màu gán ổn định theo value** (cùng value luôn cùng màu), null → badge trung tính.
   - Áp dụng cho cột "Loại" / "Cấp độ" của bảng lesson (trang edit program).
3. **Form đa ngôn ngữ: input các locale của CÙNG 1 field xếp cạnh nhau** (thay vì block tách rời theo từng locale):
   - Bố cục: label field ở trên, bên dưới là các cột locale (Tiếng Việt | Tiếng Anh | …) theo grid responsive, lặp động theo `config('translatable.locales')`.
   - Tên hiển thị locale lấy động qua `\Locale::getDisplayLanguage($locale, app()->getLocale())` (ext intl có sẵn) — KHÔNG hardcode "Tiếng Việt"/"Tiếng Anh", thêm locale mới tự có tên.
   - Áp dụng cho `programs/edit.blade.php` (name/description/sort/cover) và `lessons/_form.blade.php` (name/description/thumbnail).

Rule cập nhật: `docs/rules/11-enum.md` (đổi sang lang file), `docs/rules/05-admin-blade.md` (badge màu + layout đa ngôn ngữ cạnh nhau) + mirror `.cursor/rules/*`.

## Update 2026-06-02 — Fix lỗi upload PUT lên S3 (CORS)

**Triệu chứng:** Trang edit program, upload cover → `PUT https://s3.cloudfly.vn/local-bucket/program/cover/... net::ERR_FAILED` (s3-presigned-upload.js:61).

**Chẩn đoán (đã xác nhận):** KHÔNG phải lỗi code/signature. Preflight `OPTIONS` tới bucket trả `403 AccessDenied` và **không có header CORS** (`Access-Control-Allow-Origin` vắng). Vì request `PUT` luôn bị browser preflight (CORS), bucket `local-bucket` trên `s3.cloudfly.vn` **chưa cấu hình CORS** → browser chặn → `ERR_FAILED`. (Nếu là lỗi signature sẽ trả HTTP 403 có body, JS báo "HTTP 403" — đây là `ERR_FAILED` trước cả khi có HTTP status.)

**Cách fix (đã chốt qua ASK):**
- Tạo artisan command tái sử dụng **`php artisan s3:put-cors`** (dùng AWS SDK `PutBucketCors` qua disk `s3` đã cấu hình), chạy qua **Sail** để set CORS cho bucket. Dùng lại được cho mọi env.
- CORS policy: `AllowedOrigins = ['*']` (môi trường dev hiện tại, `APP_ENV=local`), `AllowedMethods = [PUT, GET, HEAD]`, `AllowedHeaders = ['*']`, `ExposeHeaders = ['ETag']`.

**Lưu ý:** đây là fix hạ tầng (cấu hình bucket S3), KHÔNG đổi luồng presigned trong code Laravel (`FileUploadService::createPresignedUpload` giữ nguyên).

## Update 2026-06-02 (f) — UI nâng cấp: sao rating, trái tim favorite, badge ngày, định dạng thời lượng

**Phạm vi:** chỉ thay đổi view, không migration, không logic.

1. **Rating → sao vàng:** Cột "Đánh giá" ở list program hiển thị sao vàng filled (iconify `heroicons-solid:star`, `text-warning-500`) thay cho số thuần, dựa trên `floor(rating)`; sao chưa fill dùng `heroicons-outline:star` màu xám; kèm số rating nhỏ bên cạnh.
2. **Favorites count → trái tim đỏ:** Cột "Số yêu thích" ở list program và list lesson (trong edit program) hiển thị icon trái tim đỏ (iconify `heroicons-solid:heart`, `text-danger-500`) + số bên cạnh.
3. **Cột "Ngày" lesson → badge:** Giá trị `day` trong table list lesson hiển thị dạng badge Dashcode (`badge bg-info-500 text-info-500 bg-opacity-30 rounded-3xl`) thay cho text trơn.
4. **Định dạng thời lượng → H:i:s:** Tất cả chỗ hiện tại dùng "N phút N giây" chuyển sang format `H:i:s` (ví dụ `0:30:00`). Hiện tại chỉ có 1 chỗ: cột "Tổng thời lượng" ở list program. Logic: `sprintf('%d:%02d:%02d', floor($s/3600), floor(($s%3600)/60), $s%60)`.

## Update 2026-06-06 — Click tên để mở trang sửa (list)

### Thay đổi UI
- **List program** (`/admin/programs`): cột **Tên** là link tới trang sửa program (`admin.programs.edit`). Nút **Sửa** trong menu ba chấm giữ nguyên.
- **List lesson** (table trong trang edit program): cột **Tên** là link tới trang sửa lesson (`admin.programs.lessons.edit`). Nút **Sửa** trong menu ba chấm giữ nguyên.
- Style link: theo pattern `users/index` — `text-primary-500 hover:underline font-medium`.

### Quyết định
- **2026-06-06** — Cột tên clickable trên list program + list lesson; dropdown Sửa/Xóa không đổi.

## Liên quan

- Overview: `docs/project-overview.md` (mục Program/Lesson, Subscription plans, đa ngôn ngữ).
- Rules: `docs/rules/05-admin-blade.md`, `10-database-design.md`, `11-enum.md`, `12-file-upload.md`, `14-translatable.md`, `04-api-response.md`.
- Pattern code có sẵn: `app/Admin/Http/Controllers/UserController.php`, `FileController.php`, `app/Share/Http/Controllers/Concerns/HandlesPresignedFileUpload.php`, `resources/views/admin/users/index.blade.php`.
- Models: `app/Share/Models/{Program,Lesson,Video}.php` + `*Translation.php`, `ProgramFavorite`/pivot, `SubscriptionProgramSelection.php`.
