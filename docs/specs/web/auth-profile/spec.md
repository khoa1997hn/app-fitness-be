# Spec: API profile user (GET /auth/profile)

## Bối cảnh

App native cần lấy thông tin user đang đăng nhập qua JWT, bao gồm subscription và danh sách program user đã yêu thích (để hiển thị trên màn profile/cá nhân).

> Persona: end-user đã đăng nhập (JWT).

## Phạm vi

### In-scope
- `GET /api/v1/auth/profile` — trả thông tin user hiện tại (đã có) **bổ sung** field `favorited_programs`.
- `favorited_programs`: chỉ các program user đã yêu thích (pivot `program_favorites`), sort favorite mới nhất trước.

### Out-of-scope
- Sửa endpoint favorite/unfavorite program (`POST /api/v1/programs/favorites`).
- Phân trang `favorited_programs`.
- Trả `goals`, `progress`, `is_favorited` trong mỗi program item.
- Thay đổi response các endpoint khác.

## Nghiệp vụ

1. Client gọi `GET /api/v1/auth/profile` với JWT hợp lệ.
2. BE trả thông tin user như hiện tại + `favorited_programs`.
3. `favorited_programs` = các program trong `program_favorites` của user, sort theo `created_at` pivot giảm dần (mới nhất trước).
4. User chưa yêu thích program nào → `favorited_programs` = `[]`.
5. Không token / token sai → 401.

## Input / Output

### Input
- `Authorization: Bearer <JWT>` (bắt buộc).
- Header `x-locale` (optional, vi|en) — áp dụng cho `name`, `cover` của program.

### Output (200)
```json
{
  "success": true,
  "message": "Success",
  "data": {
    "id": 1,
    "email": "user@example.com",
    "first_name": "Nguyễn",
    "last_name": "Văn A",
    "phone": null,
    "dob": null,
    "plan": "all",
    "subscription_status": "active",
    "created_at": "...",
    "updated_at": "...",
    "favorited_programs": [
      {
        "id": 1,
        "name": "Pilates",
        "cover": {
          "path": "programs/cover/abc.jpg",
          "name": "pilates.jpg",
          "extension": "jpg",
          "size": 12345,
          "url": "http://localhost/storage/programs/cover/abc.jpg"
        },
        "rating": 4.9,
        "duration_minutes": 30,
        "course_count": 12
      }
    ]
  }
}
```

### Mỗi item trong `favorited_programs`
| Field | Kiểu | Ghi chú |
|---|---|---|
| `id` | int | ID program |
| `name` | string | Theo locale |
| `cover` | object\|null | File `{ path, name, extension, size, url }`, theo locale |
| `rating` | number\|null | Từ `programs.rating` |
| `duration_minutes` | int | Tính từ tổng `duration_seconds` video theo locale |
| `course_count` | int | Số lesson của program |

### Lỗi
- 401 — thiếu/sai token.

## Acceptance criteria

- [ ] `GET /auth/profile` không token → 401.
- [ ] Có token, user chưa favorite program → 200, `favorited_programs` = `[]`, các field user giữ nguyên.
- [ ] User có favorite → 200, `favorited_programs` chỉ chứa program đã favorite, sort mới nhất trước.
- [ ] Mỗi item có đúng 6 field: id, name, cover, rating, duration_minutes, course_count.
- [ ] Đổi `x-locale` → `name`, `cover` đổi theo locale.

## API Design

### GET /api/v1/auth/profile
- **Auth**: required (Bearer JWT, `auth:api`).
- **Headers**: `x-locale` (optional, vi|en).
- **Request**: none.
- **Response 200**: user object hiện tại + `favorited_programs` (array, không phân trang).
- **Errors**: 401.

## Quyết định

- **2026-06-23** — Spec path → tạo mới `docs/specs/web/auth-profile` (user yêu cầu).
- **2026-06-23** — Shape mỗi program trong `favorited_programs` → basic: `id`, `name`, `cover`, `rating`, `duration_minutes`, `course_count` (không goals/progress/is_favorited).
- **2026-06-23** — Sort `favorited_programs` → favorite mới nhất trước (`program_favorites.created_at` DESC).
- **2026-06-23** — Tên field response → `favorited_programs`.

## Liên quan

- `app/Web/Http/Controllers/API/V1/Auth/ProfileController.php`
- `app/Share/Models/User.php` — relation `favoritePrograms()`
- `docs/specs/web/program-list/spec.md` — cách tính `duration_minutes`, `course_count`
- Pivot: `program_favorites`
