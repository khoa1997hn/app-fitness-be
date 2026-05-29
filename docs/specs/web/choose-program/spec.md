# Spec: API chọn program theo gói subscription (app)

## Bối cảnh

User mua gói **Basic** hoặc **Plus** cần chọn program (bộ môn) được unlock trước khi học.
Gói **All Access** unlock toàn bộ program, không cần chọn.
Cần lưu lựa chọn gắn với **subscription** hiện tại (để biết chọn lúc nào, bằng subscription nào) và trả về quyền loại bài (`lesson types`) theo plan.

> Persona: end-user đã đăng nhập (JWT), có subscription hợp lệ (trial / active / grace_period).

Tham chiếu quyền plan: [`docs/specs/plan_program.md`](../../plan_program.md), [`docs/project-overview.md`](../../../project-overview.md).

## Ghi chú gốc từ user (raw, không xóa)

API để user xac nhận chọn program nào với gói mua.
Nên tách phần này ra 1 table riêng lưu program_id đã chọn.
Trong api cần check xem đã chọn khi nào, chọn bằng subcription_id nào, program_id là gì, program_types được quyền. Nói chung cần đọc file quyền để nghĩ DB chuẩn lưu lại được nhiều thông tin.

Về thông tin plan nào được chọn bao nhiêu program, loại program: docs/specs/plan_program.md

## Phạm vi

### In-scope
- DB: bảng `subscription_program_selections` (subscription ↔ program, kèm `user_id` denormalize).
- `GET /api/v1/programs/selection` — trạng thái chọn program + quyền lesson types theo plan hiện tại.
- `POST /api/v1/programs/selection` — xác nhận / cập nhật danh sách program đã chọn (replace theo subscription hiện tại).
- Enum/logic map plan → `max_programs`, `allowed_lesson_types` (không lưu DB — tính từ plan).

### Out-of-scope
- Gate xem video theo program đã chọn (phase sau).
- Admin CRUD selection.
- Tự động chọn program khi mua All Access.
- Đổi selection khi subscription hết hạn / subscription mới (selection cũ vẫn gắn `subscription_id` cũ; subscription mới bắt đầu chọn lại).

## Nghiệp vụ

### Quyền theo plan (chốt từ plan_program + project-overview)

| Plan | Số program chọn | Lesson types được xem |
|------|-----------------|------------------------|
| `basic` | 1 | `level`, `special` |
| `plus` | 2 | `level`, `special`, `signature` |
| `all` | Không cần chọn (unlock all) | `level`, `special`, `signature` |

### Luồng
1. User có `validSubscription` (trial / active / grace_period).
2. **GET selection**: trả subscription hiện tại, giới hạn, lesson types được phép, danh sách program đã chọn (kèm `selected_at`).
3. **POST selection**: body `program_ids` (array int, unique).
   - Plan `all` → 422 (không cần chọn).
   - Không có subscription hợp lệ → 403.
   - `program_ids` rỗng hoặc vượt `max_programs` → 422.
   - Program id không tồn tại → 422.
   - Replace toàn bộ selection của subscription hiện tại (sync).
4. Cho phép POST lại trong cùng kỳ subscription (cập nhật lựa chọn).

### Lỗi
- Không JWT → 401.
- Không subscription hợp lệ → 403.
- Validation / plan all / program không hợp lệ → 422.

## Input / Output

### Input
- `Authorization: Bearer <JWT>`.
- `x-locale` (tên program trong response).
- **POST** body JSON: `{ "program_ids": [1, 2] }` — bắt buộc array, mỗi phần tử integer > 0, unique.

### Output

#### GET /programs/selection
```json
{
  "success": true,
  "message": "Success",
  "data": {
    "subscription_id": 10,
    "plan": "plus",
    "requires_selection": true,
    "max_programs": 2,
    "allowed_lesson_types": ["level", "special", "signature"],
    "selected_programs": [
      {
        "id": 1,
        "name": "Yoga",
        "selected_at": "2026-05-29T10:00:00+07:00"
      }
    ]
  }
}
```

- `requires_selection`: `false` khi plan = `all`.
- `max_programs`: `null` khi plan = `all`.
- `selected_programs`: rỗng nếu chưa chọn.

#### POST /programs/selection
Trả cùng shape `data` như GET (sau khi lưu).

## Mô hình dữ liệu

### `subscription_program_selections`
- `id`
- `subscription_id` FK → `subscriptions` cascadeOnDelete
- `user_id` FK → `users` cascadeOnDelete (denormalize, đồng bộ user của subscription)
- `program_id` FK → `programs` cascadeOnDelete
- `timestamps` — `created_at` = thời điểm chọn (lần đầu); cập nhật replace → record mới hoặc sync (dùng pivot timestamps)
- unique(`subscription_id`, `program_id`)

## Acceptance criteria

- [ ] User Basic chọn đúng 1 program → lưu DB, GET trả đúng.
- [ ] User Plus chọn tối đa 2 program; chọn 3 → 422.
- [ ] User All Access: GET `requires_selection=false`; POST → 422.
- [ ] Không subscription hợp lệ → 403.
- [ ] `program_ids` trùng / program không tồn tại → 422.
- [ ] POST lại trong cùng subscription → replace selection cũ.
- [ ] Response có `allowed_lesson_types` đúng theo plan.

## Quyết định

- **Bảng**: `subscription_program_selections` gắn `subscription_id` (không chỉ user) để trace chọn bằng gói nào.
- **POST = replace** toàn bộ selection của subscription hiện tại (sync).
- **All Access**: không POST; GET vẫn trả quyền lesson types.
- **`allowed_lesson_types`**: tính từ plan, không persist DB.
- **Subscription hợp lệ**: trial | active | grace_period (giống `validSubscription`).

## Liên quan

- [`docs/specs/plan_program.md`](../../plan_program.md)
- [`program-list/spec.md`](../program-list/spec.md)
- `app/Share/Models/Subscription.php`, `app/Share/Enums/Plan.php`, `app/Share/Enums/LessonType.php`
