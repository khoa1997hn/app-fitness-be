# Spec: API phát video bài học (presigned stream)

## Bối cảnh

End-user (JWT) xem video bài tập trên app native. Video lưu S3 private; client **không** nhận object key hay URL cố định từ API program detail — chỉ lấy **presigned GET** sau khi BE kiểm tra subscription + quyền theo gói/program/loại bài.

Tham chiếu quyền: [`docs/specs/plan_program.md`](../../plan_program.md), chọn program: [`choose-program/spec.md`](../choose-program/spec.md).

## Ghi chú gốc từ user (raw, không xóa)

api lấy link xem video truyền lên id bài học -> check quyền gói xem có quyền xem video ko thì mới trả về (message lỗi đa ngôn ngữ): docs/specs/plan_program.md

suggest: POST /api/videos/{videoId}/play, refresh-stream, S3 presigned GET, 403 khi không quyền, stream được trên player.

## Phạm vi

### In-scope
- `POST /api/v1/videos/{video}/play` — kiểm tra quyền, trả metadata video + `file` (presigned GET trong `file.url`).
- Auth JWT (`auth:api` middleware); controller lấy user qua `auth()->user()` (default guard `api`).
- Kiểm tra: đăng nhập, `validSubscription` (trial / active / grace_period), program đã unlock, `lesson.type` thuộc `allowed_lesson_types` của plan.
- Route model binding `Video` (id video, không phải lesson id).
- Response: `id`, `lesson_id`, `duration_seconds`, `file` (`File` JsonSerializable — `url` là presigned GET, hết hạn theo `AWS_PRESIGNED_URL_EXPIRES`).
- **Không** endpoint `refresh-stream` riêng — FE gọi lại `play` khi URL hết hạn / player lỗi *(Update 2026-05-29)*.
- Message lỗi qua `__('messages.*')` + `x-locale`.

### Out-of-scope
- Tiến độ xem / resume (FE local).
- CDN, public S3 object.
- Lưu presigned URL trong DB.
- Admin upload / CRUD video.

## Nghiệp vụ

### Điều kiện được xem
1. User có `validSubscription`.
2. **Plan `all`**: mọi program.
3. **Plan `basic` / `plus`**: `lesson.program_id` nằm trong `subscription_program_selections` của subscription hiện tại (đã chọn program).
4. `lesson.type` ∈ `allowed_lesson_types` theo plan (xem `SubscriptionService::getPlanLimits`).

### Luồng play
1. Client `POST` với `{video}` (id).
2. BE load `Video` + `lesson` + `program`; file video theo locale (`x-locale`).
3. Không đủ quyền → **403** + message cụ thể.
4. Không có file video cho locale → **404**.
5. Có quyền → trả metadata video + `file` (presigned GET trong `file.url`).
6. **Refresh URL**: khi presigned URL hết hạn (`AWS_PRESIGNED_URL_EXPIRES`) hoặc player báo lỗi truy cập, FE **gọi lại cùng endpoint** `POST .../play` — BE kiểm tra quyền lại và cấp URL mới.

### Lỗi
| Trường hợp | HTTP | Message key |
|------------|------|-------------|
| Không JWT | 401 | `authentication_error` |
| Không subscription hợp lệ | 403 | `no_active_subscription` |
| Chưa chọn program (basic/plus) | 403 | `video_program_not_selected` |
| Program chưa unlock | 403 | `video_access_denied` |
| Loại bài không thuộc gói (vd. signature + basic) | 403 | `video_lesson_type_not_allowed` |
| Video không tồn tại | 404 | `not_found_error` (model binding) |
| Chưa có file video (locale) | 404 | `video_file_not_available` |

## Input / Output

### Input
- `Authorization: Bearer <JWT>`.
- `x-locale` (optional).
- Path `{video}` — integer id bảng `videos`.

### Output (200)
```json
{
  "success": true,
  "message": "Success",
  "data": {
    "id": 1,
    "lesson_id": 10,
    "duration_seconds": 600,
    "file": {
      "path": "lesson/video/....mp4",
      "name": "....mp4",
      "extension": "mp4",
      "size": 10485760,
      "url": "https://..."
    }
  }
}
```
(FE dùng `file.url` cho player. Gọi lại `play` khi URL hết hạn.)

## Acceptance criteria

- [ ] User All Access + subscription hợp lệ → 200 + `file.url` playable.
- [ ] User Basic đã chọn program A, video thuộc lesson program A, type level → 200.
- [ ] User Basic chưa chọn program → 403 `video_program_not_selected`.
- [ ] User Basic, lesson signature → 403 `video_lesson_type_not_allowed`.
- [ ] User không subscription hợp lệ → 403 `no_active_subscription`.
- [ ] Gọi lại `play` sau khi URL hết hạn → 200 + `file.url` mới.

## Quyết định

- **2026-05-29** — Param route = **video id** (`Video` model), không lesson id (một lesson có thể nhiều video).
- **2026-05-29** — Prefix API ` /api/v1/` (chuẩn Web V1), không `/api/videos` gốc trong suggest.
- **2026-05-29** — Thời hạn presigned = `config('app_file.presigned_expires_minutes')` (env `AWS_PRESIGNED_URL_EXPIRES`).
- **2026-05-29** — Tái dùng logic plan/program/lesson type từ `SubscriptionService::getPlanLimits` + selections; không duplicate bảng quyền.
- **2026-05-29** — Bỏ `refresh-stream`; FE refresh bằng cách gọi lại `play`.
- **2026-05-29** — Response play gồm metadata video + `file` + `stream_url`.
- **2026-05-29** — Web controller: `auth()->user()`, không `auth('api')->user()` (chỉ login/refresh/logout chỉ định guard `api`).

## Update 2026-06-06 — Bỏ `stream_url` trùng `file.url`

### Thay đổi
- Response `POST /api/v1/videos/{video}/play` **không còn** field `stream_url`.
- Presigned GET chỉ qua `file.url` (chuẩn `File` JsonSerializable, rule `12-file-upload.md`).
- Lý do: `stream_url` và `file.url` cùng `FileUploadService::getUrl(path)` — trùng lặp, BE sinh URL 2 lần mỗi request.

### FE
- Player dùng `data.file.url` thay `data.stream_url`.
- Refresh URL: vẫn gọi lại `POST .../play`.

### Quyết định
- **2026-06-06** — Bỏ `stream_url`; FE dùng `file.url` duy nhất.

## Liên quan

- [`docs/specs/plan_program.md`](../../plan_program.md)
- [`docs/specs/web/choose-program/spec.md`](../choose-program/spec.md)
- [`docs/specs/shared/s3-presigned-upload/spec.md`](../../shared/s3-presigned-upload/spec.md)
- `docs/rules/12-file-upload.md`, `docs/rules/04-api-response.md`
