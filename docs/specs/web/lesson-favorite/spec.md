# Spec: API yêu thích / bỏ yêu thích bài học (app)

## Bối cảnh

User (đã đăng nhập) đánh dấu **bài học (lesson)** yêu thích / bỏ yêu thích.
Có màn "Favorites" liệt kê các bài học đã yêu thích (flatten theo bài học, kèm thông tin program).
Cần cờ `is_favorited` của user hiện tại khi trả lesson ở program detail.
Model `Lesson` + program detail (`GET /api/v1/programs/{program}`) đã có (xem `program-detail/spec.md`).

> Persona: end-user đã đăng nhập (JWT).

## Ghi chú gốc từ user (raw, không xóa)

- api yêu thích, bỏ yêu thích bài học (cần trả về thông tin auth đang yêu thích hay chưa ở list và detail)

## Phạm vi

### In-scope
- DB: bảng pivot `lesson_favorites` (user ↔ lesson). *(Đặt tên do mình chọn, đã note ở Quyết định.)*
- `POST /api/v1/lessons/{lesson}/favorite` — đánh dấu yêu thích.
- `DELETE /api/v1/lessons/{lesson}/favorite` — bỏ yêu thích.
- `GET /api/v1/lessons/favorites` — list bài học đã yêu thích (flatten, phân trang, mới nhất trước).
- Thêm `is_favorited` (bool) vào lesson item trong **program detail** + favorites list.

### Out-of-scope
- Yêu thích program (chỉ lesson).
- Thêm `is_favorited` vào program list (home) — home trả program, không có lesson.
- Trả link/file xem video ở mọi response (giữ nguyên nguyên tắc cũ).
- Gate theo subscription plan.

## Nghiệp vụ

- User yêu thích / bỏ yêu thích 1 lesson (theo user JWT hiện tại).
- **Idempotent**: favorite cái đã favorite → vẫn 200 (không tạo trùng); unfavorite cái chưa favorite → vẫn 200.
- `is_favorited` tính theo user hiện tại cho mỗi lesson.
- Lesson không tồn tại → 404 (route model binding).
- Không token / token sai → 401.

## Input / Output

### Input
- `Authorization: Bearer <JWT>` (bắt buộc cho mọi endpoint).
- `x-locale` (đa ngôn ngữ name/description/thumbnail).
- Route param `{lesson}` (favorite/unfavorite).
- Query `page`, `per_page` (favorites list; mặc định `per_page=10`, tối đa `50`).

### Output

#### POST/DELETE favorite
```json
{ "success": true, "message": "Success", "data": null }
```

#### GET lessons/favorites
Flatten theo bài học, không group theo program. Mỗi item kèm thông tin program.
KHÔNG trả link/file video.
```json
{
  "success": true,
  "message": "Success",
  "data": {
    "items": [
      {
        "id": 12,
        "name": "Day 1 - Warm up",
        "thumbnail": { "path": "...", "name": "...", "extension": "jpg", "size": 1024, "url": "http://..." },
        "duration_seconds": 600,
        "is_favorited": true,
        "program": { "id": 1, "name": "7 Day Training Split", "days": 7 }
      }
    ],
    "pagination": { "current_page": 1, "per_page": 10, "total": 3, "last_page": 1 }
  }
}
```
- `program.days` = **max `day`** của các lesson thuộc program đó (theo figma badge "7 days").
- `is_favorited` luôn `true` trong list này (đều là bài đã yêu thích).

#### Lesson item trong program detail
Thêm field `is_favorited` (bool) vào mỗi lesson item (các nhóm level/special/signature).

## Acceptance criteria

- [ ] `POST /lessons/{lesson}/favorite` → lesson được đánh dấu; gọi lại vẫn 200, không trùng record.
- [ ] `DELETE /lessons/{lesson}/favorite` → bỏ đánh dấu; gọi khi chưa favorite vẫn 200.
- [ ] `GET /programs/{program}` → mỗi lesson item có `is_favorited` đúng theo user.
- [ ] `GET /lessons/favorites` → trả các bài đã yêu thích (mới nhất trước), phân trang, mỗi item có `program:{id,name,days}`, `is_favorited=true`, không có link video.
- [ ] Lesson không tồn tại → 404. Không token → 401.

## Quyết định (chốt qua ASK + figma)

- **Đối tượng**: chỉ lesson (bài học).
- **Endpoint**: REST tách `POST` (thêm) / `DELETE` (bỏ) tại `/lessons/{lesson}/favorite`.
- **Idempotent**: luôn 200 (`syncWithoutDetaching` / `detach`).
- **Favorites list**: có; flatten theo lesson, phân trang, mới yêu thích trước.
- **`is_favorited`**: chỉ ở program detail + favorites list (không ở home).
- **Item favorites**: `id, name, thumbnail, duration_seconds, is_favorited, program:{id,name,days}`. `program.days` = max day của lesson trong program (figma `lesson_favourited_list.png`).
- **Response favorite action**: `data: null` (chỉ success).
- **Tên bảng pivot** (mình chọn): `lesson_favorites(user_id, lesson_id, timestamps)`, unique(user_id, lesson_id), FK cascade khi xóa user/lesson.
- **Envelope phân trang** (mình chọn, chưa có convention V1): `data: { items, pagination:{current_page,per_page,total,last_page} }`.

## Liên quan

- [`program-detail/spec.md`](../program-detail/spec.md)
- Figma: `docs/specs/web/figma/lesson_favourited_list.png`
- `app/Web/Http/Controllers/API/V1/ProgramController.php`, `app/Share/Models/Lesson.php`, `User.php`
