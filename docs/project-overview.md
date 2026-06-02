# Project overview

File này MỌI LLM phải đọc TRƯỚC khi làm bất kỳ task nào trên dự án.

## Sản phẩm

Backend API cho **native app tập thể thao tại nhà qua video**.

- **End-user (app)**: người dùng cuối, mở app, mua gói subscription để unlock video bài tập.
- **Admin (web)**: nhân viên vận hành — quản lý nội dung (program, bài học, banner), user, thanh toán.

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

- User (xem, xóa, export CSV).
- Subscription / thanh toán (ai mua gói gì, lúc nào, còn hạn không) — xem lịch sử.
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
- **Admin (web)**: session-based, guard `auth:admin` riêng (KHÔNG dùng guard `web` mặc định).

### Admin UI
- Laravel Blade (KHÔNG dùng Filament / Nova / Backpack).
- HTML template mẫu trong `resources/dashcode/`. Asset đã build sẵn ở `public/dashcode/`.
- Toàn bộ label/message/button — tiếng Việt.

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
- Auth (register / login / profile / logout / refresh / delete account) — JWT. User soft-delete (`DELETE /api/v1/auth/me`); đăng nhập lại sau khi xóa bị chặn. Google Play subscription được cancel phía provider trước khi xóa; Apple bỏ qua (không hỗ trợ outbound cancel).
- Admin login, dashboard placeholder, Users list/delete/export CSV.
- Subscription core: model + service + listener Apple/Google + IAP webhook.
- Banner (list API + multi-language).
- Program / Lesson / Video: model + migration + translatable (Lesson có enum `type`/`level`). API list program Home (`GET /api/v1/programs`) + chi tiết program + lessons grouped (`GET /api/v1/programs/{program}`, auth JWT).
- Yêu thích bài học: pivot `lesson_favorites` (user ↔ lesson). API favorite/unfavorite (`POST|DELETE /api/v1/lessons/{lesson}/favorite`) + list yêu thích (`GET /api/v1/lessons/favorites`, flatten + phân trang) + cờ `is_favorited` trong program detail.
- Yêu thích program: pivot `program_favorites` (user ↔ program, một user có thể yêu thích nhiều program). API `POST /api/v1/programs/favorites` body `program_ids` (thêm, không ghi đè favorite cũ, idempotent) + cờ `is_favorited` trên `GET /api/v1/programs` (Home); thứ tự list Home = favorite trước (mới nhất trước), còn lại theo `sort`/`id`.
- Chọn program theo gói: bảng `subscription_program_selections` (subscription ↔ program). API `GET|POST /api/v1/programs/selection` — trạng thái chọn + xác nhận program (Basic 1, Plus 2, All Access không cần chọn); `GET /api/v1/programs/purchased` — program đã mua + subscription (giá, ngày, flags UI Figma); `POST /api/v1/subscriptions/cancel` — hủy auto-renew (Google provider).
- Phát video: `POST /api/v1/videos/{video}/play` — kiểm tra subscription + program/lesson type, trả metadata video + presigned GET (`VideoPlayService`); FE gọi lại `play` khi URL hết hạn.
- Tiến độ xem: `POST /api/v1/videos/{video}/watch-progress` (start/progress/completed); tổng hợp % video → lesson → program; các API program/lesson/favorites/play trả `progress: {watched_seconds, completed_percent}`.
- Program đang học dở: `GET /api/v1/programs/left-off` — trả program có `last_watched_at` mới nhất kèm `last_lesson` + `video`.
- Admin CRUD Program + Lesson + Video: `ProgramController` (index/edit/update/destroy — KHÔNG create, 7 program cố định) + `LessonController` nested (create/store/edit/update/destroy). Views Blade `admin/programs/{index,edit}`, `admin/lessons/{create,edit,_form}`; relation `favorites()` trên Program/Lesson; label tiếng Việt enum `LessonType`/`Level` qua `getDescription()`. Upload video presigned PUT (lưu key S3) + xem presigned GET; `duration_seconds` nhập tay. Route: `routes/admin.php` (2 resource); menu sidebar "Bộ môn".

### CHƯA có (phase tiếp)
- Admin: subscription/payment view.

> LLM thực hiện task: nếu task chạm các module **CHƯA có**, dùng `/implement-spec`. Nếu chạm module **đã có** mà phải đổi, dùng `/update-spec`.

## Quy ước nghiệp vụ quan trọng

- "Program" trong code/DB = "bộ môn" trong giao tiếp với user/PO.
- "Lesson" = "bài học" = "video".
- "Level" là phân loại của Lesson trong 1 Program. Luôn 3 giá trị: `Beginner`, `Intermediate`, `Advanced` → BẮT BUỘC Enum (`docs/rules/02-code-quality.md`).
- "Special", "Signature" là 2 loại bài học tách riêng level. Khi mô hình hóa, cân nhắc Enum loại bài tập: `Level`, `Special`, `Signature` (1 lesson thuộc đúng 1 loại).
- Quyền access: tính theo `(user.plan, lesson.program, lesson.type)`.

## Khi mơ hồ

Đọc `docs/rules/00-core.md`. Mơ hồ về nghiệp vụ Program/Lesson/Plan/Payment → DỪNG, hỏi user qua AskUserQuestion. KHÔNG bịa.
