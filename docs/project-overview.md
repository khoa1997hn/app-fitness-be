# Project overview

File này MỌI LLM phải đọc TRƯỚC khi làm bất kỳ task nào trên dự án.

## Sản phẩm

Backend này phục vụ **hai sản phẩm tách biệt**, chỉ dùng chung `users` và `/auth/*`:

1. **App video** — native app tập tại nhà. Người dùng mua subscription IAP để xem video. Admin nội dung chạy trong mục Quản lý app trên React, chỉ admin.
2. **Hệ thống phòng tập offline** — web `fitness-web`. Hội viên mua gói buổi, đặt lịch, đăng ký sự kiện, thanh toán (tạm mock). Lễ tân, HLV, admin quản lý trên React. Spec: [`docs/specs/gym/`](specs/gym/README.md), lộ trình: [`fitness-web/plan/`](../../fitness-web/plan/README.md).

**Không trộn hai bên:** entity phòng tập không tham chiếu Program/Lesson/Subscription và ngược lại. Phần "Nghiệp vụ cốt lõi" dưới đây là của **app video**.

- **End-user (app)**: người dùng cuối, mở app, mua gói subscription để unlock video bài tập.
- **Admin app (React)**: role admin — quản lý nội dung (program, bài học, banner, combo), user, subscription và lịch sử IAP.

## Nghiệp vụ cốt lõi

### Hai khái niệm chính

- **Program** (bộ môn) — chuyên ngành tập (Yoga, Pilates, ...).
- **Lesson** (bài học) — 1 video trong 1 program.

### 7 program (giai đoạn hiện tại)

Cố định trong giai đoạn đầu, nhưng PHẢI lưu DB vì phase sau có thể thêm:

1. Yoga
2. Mat Pilates
3. Reformer Pilates
4. Sculpt
5. Breathwork
6. Wellness
7. Barre

### Phân loại bài học trong 1 program

- **Theo level** — luôn có 3 level: `Beginner`, `Intermediate`, `Advanced`.
- **Special** — bài đặc biệt (không thuộc level).
- **Signature** — bài cao cấp nhất, chỉ user gói Plus và All Access mới truy cập được.

### Subscription plans

| Plan | Số program unlock | Bài tập unlock |
|---|---|---|
| Basic | 1 program (user chọn) | 3 level + Special |
| Plus | 2 program (user chọn) | 3 level + Special + Signature |
| All Access | Tất cả program | Tất cả loại bài |

Giá cụ thể từng plan → env `PLAN_<TIER>_PRICE` (xem `.env.example`).

### Admin quản lý

- User (xem danh sách, chi tiết, xóa, export CSV).
- Subscription / thanh toán — trang chi tiết user hiển thị subscription hiện tại + lịch sử giao dịch (union google_subscriptions + apple_subscriptions).
- Program (CRUD + ảnh/cover).
- Lesson (CRUD + upload video).
- Banner (CRUD đa ngôn ngữ vi/en).

## Stack thực tế

### Backend
- **PHP** `^8.2`
- **Laravel** `^12.0`
- **MySQL 8.0** (qua Sail trong Docker)
- **Redis** (cache/queue tùy chọn)

### Authentication
- **End-user (API)**: JWT qua `tymon/jwt-auth`. Token gửi qua header `Authorization: Bearer <token>`.
- **Admin app**: JWT + `permission:app.manage` (chỉ admin); route Blade `/admin` đã ngừng.
- **Nhân viên phòng tập**: JWT như end-user, phân quyền theo `users.role` (`admin`, `receptionist`, `trainer`, `member`) + `config/permissions.php`.

### Admin UI (hai sản phẩm)
- **App video (React)**: menu Quản lý app `/admin/app/*`, API `/api/v1/admin/app/*`, JWT + quyền `app.manage` chỉ admin. Program/Lesson/video, Banner, Combo, Users/subscription/IAP và CSV.
- **React (`fitness-web`)**: admin **phòng tập**, JWT + `users.role`, API `/api/v1/admin/*` + middleware `permission:*`. Xem [`docs/guides/admin-dual-surface.md`](guides/admin-dual-surface.md).

### Email thông báo
- Bốn mẫu email (xác thực tài khoản, liên kết đặt lại mật khẩu, hủy lịch, thông báo phòng tập) hiển thị **cả tiếng Việt và tiếng Anh trong cùng email**, kể cả subject và nội dung động (quyết định 2026-11-04).
- Dùng chung header/logo, lời chào, nội dung, nút truy cập, chữ ký, đường dẫn dự phòng và footer KORE; hỗ trợ HTML và plain text.
- Link ứng dụng lấy từ `config('app.frontend_url')` / `FRONTEND_URL`: xác thực → link token; đặt lại mật khẩu → `/reset-password?token=...&email=...` (token một lần, 60 phút; chỉ đổi password khi xác nhận); hủy lịch → lịch sử tập; chờ duyệt Private → chi tiết booking; phân lịch HLV → lịch dạy; thanh toán → gói/tiện ích/trị liệu/lịch sử tập hoặc tài khoản sự kiện của hội viên, chi tiết giao dịch của admin.
- Các thông báo trong ứng dụng giữ luồng hiện tại; bản PDF điều khoản đã ký vẫn được đính kèm khi gửi email thanh toán.

### API
- Web API V1 nằm trong `app/Web/Http/Controllers/API/V1/`.
- Response format chuẩn `ResponseAPI::success() / ::error()` — `{ success, message, data, errors? }`.
- KHÔNG return full model — phải map từng field (xem `docs/rules/04-api-response.md`).
- OpenAPI docs qua `darkaonline/l5-swagger` v10 + PHP 8 Attributes (`#[OA\...]`).

### Đa ngôn ngữ (BẮT BUỘC cho Web API)

**Project ĐA NGÔN NGỮ CẢ CONTENT, không chỉ label.** Tức là 1 banner / lesson / program có thể có ảnh, mô tả, link, sort order khác nhau giữa `vi` và `en`.

- Package: `astrotomic/laravel-translatable`.
- Locales: `vi` (default), `en`. Config: `config/translatable.php`.
- Header API: `x-locale` (optional, default từ `config('app.locale')`).
- Pattern: 2 table (`<entities>` + `<entity>_translations`). Xem `docs/rules/14-translatable.md` và mẫu [`Banner`](app/Share/Models/Banner.php) + [`BannerTranslation`](app/Share/Models/BannerTranslation.php).
- **Khi thiết kế DB cho entity mới** (Program, Lesson, ...): tự hỏi "Tiếng Nhật field này có khác không?" CÓ → bảng translation. Nghi ngờ → hỏi user, KHÔNG bịa.

### Payment / IAP

**Phạm vi hiện tại**: Google Play (Android) + Apple App Store (iOS). KHÔNG có bên thứ 3 (Stripe/Paypal) trong phase này.

**Package**: `imdhemy/laravel-purchases` — handle webhook + receipt verification.

**Kiến trúc service** (`app/Share/Services/Subscription/`):

- `SubscriptionService` — logic chung (kích hoạt, hết hạn, gia hạn ở mức nghiệp vụ).
- `AppleService`, `GoogleService` — logic RIÊNG cho từng provider.
- `TrialService` — xử lý trial.
- → Khi thêm provider mới (Stripe, in-house...) → thêm service mới + ghép vào `SubscriptionService`.

**Listeners** (`app/Share/Listeners/Subscriptions/`):
- Google: 7 event (Purchased, Renewed, Canceled, Expired, GracePeriod, Revoked, ...).
- Apple: 8 event (InitialBuy, DidRenew, DidFailToRenew, Cancel, Expired, GracePeriodExpired, Refund, ...).
- Mỗi nhóm có `BaseGoogleListener` / `BaseAppleListener` để share logic chung.

**DB**:
- `subscriptions` — bảng master, 1 record / lần mua gói.
- `apple_subscriptions`, `google_subscriptions` — chi tiết per-provider.
- User có cột subscription được join sẵn.

**Lưu ý log/audit**: hiện chưa có table `payment_logs` raw payload. Khi cần trace ngược raw webhook → cân nhắc thêm 1 bảng append-only (đề xuất ở phase sau, hỏi user trước khi thêm).

## Module hiện có vs chưa có

### Đã có
- Auth (register / login / profile / logout / refresh / delete account) — JWT. `GET /api/v1/auth/profile` trả thông tin user + `favorited_programs` (program đã yêu thích, basic card, sort mới nhất trước). Register: `phone` và `dob` tùy chọn. User soft-delete (`DELETE /api/v1/auth/me`); đăng nhập lại sau khi xóa bị chặn. Google Play subscription được cancel phía provider trước khi xóa; Apple bỏ qua (không hỗ trợ outbound cancel).
- React quản trị app: Users list/delete/export CSV; chi tiết khách hàng + subscription/lịch sử IAP; sửa/tạo subscription thủ công (`SubscriptionService::adminUpsert`, provider `admin`). Ngừng Blade login/dashboard/routes. Nguồn tài khoản app/web/nhân viên + creator; backfill trước 2026-11-02 app, từ ngày đó web (giữ dữ liệu và timestamps).
- Subscription core: model + service + listener Apple/Google + IAP webhook.
- Banner (list API + multi-language).
- Program / Lesson / Video: model + migration + translatable (Lesson có enum `type`/`level`). API list program Home (`GET /api/v1/programs`) + chi tiết program + lessons grouped (`GET /api/v1/programs/{program}`, auth JWT); mỗi lesson trong program detail có `can_access` theo gate subscription dùng chung với API phát video.
- Yêu thích bài học: pivot `lesson_favorites` (user ↔ lesson). API favorite/unfavorite (`POST|DELETE /api/v1/lessons/{lesson}/favorite`) + list yêu thích (`GET /api/v1/lessons/favorites`, flatten + phân trang) + cờ `is_favorited` trong program detail.
- Chi tiết bài học: `GET /api/v1/lessons/{lesson}` — auth JWT, trả metadata lesson (giống lesson item program detail: `video_id`, `progress`, `is_favorited`, …); không trả link video.
- Yêu thích program: pivot `program_favorites` (user ↔ program, một user có thể yêu thích nhiều program). API `POST /api/v1/programs/favorites` body `program_ids` (thêm, không ghi đè favorite cũ, idempotent) + cờ `is_favorited` trên `GET /api/v1/programs` (Home); thứ tự list Home = favorite trước (mới nhất trước), còn lại theo `sort`/`id`.
- Chọn program theo gói: bảng `subscription_program_selections` (subscription ↔ program). API `GET|POST /api/v1/programs/selection` — trạng thái chọn + xác nhận program (Basic 1, Plus 2, All Access không cần chọn); `GET /api/v1/programs/purchased` — program đã mua + subscription (giá, ngày, flags UI Figma); `POST /api/v1/subscriptions/cancel` — hủy auto-renew (Google provider).
- Phát video: `POST /api/v1/videos/{video}/play` — kiểm tra subscription + program/lesson type, trả metadata video + presigned GET (`VideoPlayService`); FE gọi lại `play` khi URL hết hạn.
- Tiến độ xem: `POST /api/v1/videos/{video}/watch-progress` (start/progress/completed); tổng hợp % video → lesson → program; các API program/lesson/favorites/play trả `progress: {watched_seconds, completed_percent}`.
- Program đang học dở: `GET /api/v1/programs/left-off` — trả program có `last_watched_at` mới nhất kèm `last_lesson` + `video`.
- React admin CRUD Program + Lesson + Video: `ProgramController` (index/edit/update/destroy — KHÔNG create, 7 program cố định) + `LessonController` nested (create/store/edit/update/destroy). Views Blade `admin/programs/{index,edit}`, `admin/lessons/{create,edit,_form}`; relation `favorites()` trên Program/Lesson; label tiếng Việt enum `LessonType`/`Level` qua `getDescription()`. Upload video presigned PUT (lưu key S3) + xem presigned GET; `duration_seconds` nhập tay. Route API: `/api/v1/admin/app/programs` + nested lessons; React mục Quản lý app → Bộ môn. Views Blade cũ đã ngừng.
- React admin CRUD Banner: `BannerController` (index/create/store/edit/update/destroy). Views Blade `admin/banners/{index,create,edit}`; list phân trang 20/trang sort `id` DESC, hiển thị locale vi; form đa ngôn ngữ (vi/en) gồm `image` (presigned PUT, `FileType::BannerImage`), `link_url`, `order`, `is_active` per locale + `description` dùng chung; React mục Quản lý app → Banner; API `/api/v1/admin/app/banners`. Views Blade cũ đã ngừng. Model `Banner`/`BannerTranslation` + migration đã có từ trước.
- Combo (gói nhiều program): model `Combo` + `combo_translations` (name, cover), pivot `combo_program` (2–7 program, sort), `ComboInfo` + `combo_info_translations` (tối đa 3 info: icon PNG dùng chung + text đa ngôn ngữ max 100 ký tự). API `GET /api/v1/combos` (list card: id, name, cover, program_count, infos; sort combo có program favorite lên trước) + `GET /api/v1/combos/{combo}` (detail kèm programs full như program detail). React CRUD `AppAdmin/ComboController`; API `/api/v1/admin/app/combos`, mục Quản lý app → Combo. Views Blade cũ đã ngừng.

- Role + phân quyền phòng tập: `users.role`, middleware `permission:*`, `GET /auth/profile` trả `role`. Spec [`gym/roles`](specs/gym/roles/spec.md).
- Gói tập phòng tập: `Package` + `package_translations`. API `GET /api/v1/packages[/{id}]` + CRUD `/api/v1/admin/packages`. Spec [`gym/packages`](specs/gym/packages/spec.md).

- Đơn hàng phòng tập: `Order`, `UserPackage`, `UserTherapy`, `TherapyService`; ký điều khoản, thanh toán **mock**, cấp gói qua `OrderService`; nhân viên tạo tài khoản và đơn hộ khách tại quầy. Spec [`gym/orders`](specs/gym/orders/spec.md).
- Thời khóa biểu và đặt lịch gói thường: `FitnessClass`, `ClassSchedule`, `Booking`, `BookingService`; trừ buổi khi đặt, hủy trước 8 tiếng được hoàn buổi, vắng vẫn mất buổi; lọc off-peak. Spec [`gym/bookings`](specs/gym/bookings/spec.md).
- Yêu cầu Private, Trị liệu: `PrivateRequest`, `TherapyRequest`; suất người thêm theo tài khoản, chỉ gói `private_pack`, số dư tính từ đơn và yêu cầu (`ExtraPeopleService`). Spec [`gym/private-therapy`](specs/gym/private-therapy/spec.md).
- Điểm danh lớp gói thường, lịch dạy, yêu cầu hủy của HLV. Spec [`gym/attendance-teaching`](specs/gym/attendance-teaching/spec.md).
- Quản lý user, khóa tài khoản (chặn cả token còn hạn trên mọi API), hồ sơ hội viên, đổi mật khẩu, thông báo trong web. Spec [`gym/users-account`](specs/gym/users-account/spec.md).
- Sự kiện: vé miễn phí và có phí, giữ vé 30 phút khi chờ thanh toán, check-in, hủy sự kiện. Spec [`gym/events`](specs/gym/events/spec.md).
- Redlight theo từng buổi cho lớp Barre, Pilates. Spec [`gym/redlight`](specs/gym/redlight/spec.md).
- Báo cáo, tổng quan theo vai trò. Spec [`gym/reports`](specs/gym/reports/spec.md).

### CHƯA có (phase tiếp)

Hệ thống phòng tập: hết các phase theo [`fitness-web/plan/`](../../fitness-web/plan/README.md). Phần để sau: cổng thanh toán thật, xuất file, khóa đào tạo HLV, hồ sơ HLV (`fitness-web/plan/deferred.md`).

> LLM thực hiện task: nếu task chạm các module **CHƯA có**, dùng `/implement-spec`. Nếu chạm module **đã có** mà phải đổi, dùng `/update-spec`.

## Quy ước nghiệp vụ quan trọng

- "Program" trong code/DB = "bộ môn" trong giao tiếp với user/PO.
- "Lesson" = "bài học" = "video".
- "Level" là phân loại của Lesson trong 1 Program. Luôn 3 giá trị: `Beginner`, `Intermediate`, `Advanced` → BẮT BUỘC Enum (`docs/rules/02-code-quality.md`).
- "Special", "Signature" là 2 loại bài học tách riêng level. Khi mô hình hóa, cân nhắc Enum loại bài tập: `Level`, `Special`, `Signature` (1 lesson thuộc đúng 1 loại).
- Quyền access: tính theo `(user.plan, lesson.program, lesson.type)`.

## Khi mơ hồ

Đọc `docs/rules/00-core.md`. Mơ hồ về nghiệp vụ Program/Lesson/Plan/Payment → DỪNG, hỏi user qua AskUserQuestion. KHÔNG bịa.
