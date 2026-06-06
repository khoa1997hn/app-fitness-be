# Bug report: Video play so sánh lesson type sai kiểu

## Mô tả

**Triệu chứng:** `POST /api/v1/videos/{video}/play` trả 500:
`Argument #1 ($type) must be of type App\Share\Enums\LessonType, string given` tại `VideoPlayService.php` line 39.

**Steps reproduce:**
1. User đăng nhập, có subscription hợp lệ.
2. Gọi `POST /api/v1/videos/{id}/play`.
3. Lỗi type hint trong closure `contains()`.

**Mong đợi:** 200 hoặc 403 theo quyền — không 500.

## Phân loại

- Mức độ: **cao**
- Phạm vi: `VideoPlayService::streamGate`

## Nguyên nhân gốc

`SubscriptionService::getPlanLimits()` trả `allowed_lesson_types` là **`list<string>`** (giá trị enum string: `level`, `special`, …). `VideoPlayService` dùng closure type-hint `LessonType $type` trong `Collection::contains()` — collection chứa string, không phải enum instance.

## Cách fix

So sánh trực tiếp `$lesson->type->value` với mảng string:
`in_array($lesson->type->value, $limits['allowed_lesson_types'], true)`.

## Files đã sửa

- `app/Share/Services/Video/VideoPlayService.php` — bỏ closure sai type, dùng `in_array`

## Verify

- [ ] `POST /videos/{video}/play` không còn 500 type error
- [ ] Basic user + lesson signature → 403 `video_lesson_type_not_allowed`
- [x] `pint` pass
