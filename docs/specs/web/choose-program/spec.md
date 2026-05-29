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

api show ấy, mình làm thành api này có hợp lý hơn ko? Hay tách 1 api mới nhỉ?
api lấy danh sách program đã mua (kể cả đang active lẫn cancel nói chung toàn bộ trạng thái, cần bổ sung thêm ngày bắt đầu, ngày gia hạn tiếp theo so với design, trạng thái của subcription ấy) *(Update 2026-05-29)*

cần check figma purchased_program_1/2 để biết field và api cần thêm *(Update 2026-05-29)*

## Phạm vi

### In-scope
- DB: bảng `subscription_program_selections` (subscription ↔ program, kèm `user_id` denormalize).
- `GET /api/v1/programs/selection` — trạng thái chọn program + quyền lesson types theo plan **subscription hợp lệ** (màn chọn program).
- `POST /api/v1/programs/selection` — xác nhận / cập nhật danh sách program đã chọn (replace theo subscription hiện tại).
- `GET /api/v1/programs/purchased` — danh sách program đã mua kèm thông tin subscription (mọi status: active, cancelled, expired, …) *(Update 2026-05-29)*.
- `POST /api/v1/subscriptions/cancel` — hủy gia hạn tự động (nút **CANCEL RENEWAL** trên Figma); Google gọi provider API, Apple no-op *(Update 2026-05-29)*.
- Enum/logic map plan → `max_programs`, `allowed_lesson_types` (không lưu DB — tính từ plan).

### Out-of-scope
- Gate xem video theo program đã chọn (phase sau).
- Admin CRUD selection.
- Tự động chọn program khi mua All Access.
- Đổi selection khi subscription hết hạn / subscription mới (selection cũ vẫn gắn `subscription_id` cũ; subscription mới bắt đầu chọn lại).
- **RENEW SUBSCRIPTION** (Figma): FE mở store + gọi `POST /subscriptions/iap/{google|apple}/verify` có sẵn — không thêm API renew riêng.
- `plan_label` marketing (vd. "Platinum" trên Figma): FE map từ `plan` enum.

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

#### GET /programs/purchased *(Update 2026-05-29, bổ sung Figma 2026-05-29)*

Màn **Purchased program** ([`purchased_program_1.png`](../figma/purchased_program_1.png) cancelled, [`purchased_program_2.png`](../figma/purchased_program_2.png) cancel renewal).

Một subscription / user + danh sách program (card: thumbnail, tên program, badge plan, giá, status).

```json
{
  "success": true,
  "message": "Success",
  "data": {
    "subscription": {
      "id": 10,
      "plan": "plus",
      "status": "active",
      "provider": "google_iap",
      "amount": 1999000,
      "currency": "VND",
      "auto_renew": true,
      "started_at": "2026-01-01T00:00:00+07:00",
      "expires_at": "2026-06-01T00:00:00+07:00",
      "renews_at": "2026-06-01T00:00:00+07:00",
      "cancelled_at": null,
      "show_plan_ends_notice": false,
      "can_cancel_renewal": true,
      "can_renew": false,
      "requires_selection": true,
      "max_programs": 2,
      "allowed_lesson_types": ["level", "special", "signature"]
    },
    "programs": [
      {
        "id": 1,
        "name": "7 Day Training Split",
        "cover": { "path": "...", "url": "..." },
        "selected_at": "2026-05-29T10:00:00+07:00"
      }
    ]
  }
}
```

**Field subscription (theo Figma):**

| Field | Nguồn / logic |
|-------|----------------|
| `plan` | enum `basic` / `plus` / `all` — FE map label marketing (Platinum, …) |
| `status` | `subscriptions.status` — hiển thị "Canceled" trên card khi `cancelled` hoặc đã hủy gia hạn |
| `amount`, `currency` | `subscriptions` — giá hiển thị card |
| `started_at` | `created_at` |
| `expires_at` | `expires_at` — hết kỳ billing (banner "plan ends at the end of your billing period") |
| `renews_at` | `expires_at` khi `auto_renew=true` và status hợp lệ; else `null` |
| `cancelled_at` | `cancelled_at` |
| `auto_renew` | `auto_renew` |
| `show_plan_ends_notice` | `true` khi `auto_renew=false`, `expires_at` > now, status ∈ {trial, active, grace_period} |
| `can_cancel_renewal` | subscription hợp lệ + `auto_renew=true` + `cancelled_at` null → nút **CANCEL RENEWAL** |
| `can_renew` | `status` ∈ {cancelled, expired} hoặc đã có `cancelled_at` → nút **RENEW SUBSCRIPTION** (FE mở IAP) |

- Không có subscription → `subscription: null`, `programs: []` (200).
- Plan `all`: `programs` = toàn bộ program; `selected_at` có thể `null`.
- Plan `basic`/`plus`: `programs` từ `subscription_program_selections`.

#### POST /subscriptions/cancel *(Update 2026-05-29 — Figma CANCEL RENEWAL)*

- Auth JWT. Subscription hợp lệ + `can_cancel_renewal=true`.
- Google: `SubscriptionManager::cancelProvider()` — không cập nhật DB trực tiếp (chờ webhook).
- Apple: 200 + message hướng dẫn hủy qua App Store (Imdhemy không hỗ trợ outbound cancel).
- Google API fail → 500.
- Đã hủy gia hạn rồi (`can_cancel_renewal=false`) → 422.

```json
{ "success": true, "message": "Success", "data": null }
```

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
- [ ] `GET /programs/purchased` trả subscription (mọi status) + programs; dates đúng mapping.
- [ ] User cancelled/expired vẫn GET purchased → 200 (không 403).
- [ ] GET purchased trả đủ field Figma: amount, currency, expires_at, flags notice/actions.
- [ ] POST /subscriptions/cancel: Google hủy auto-renew; Apple trả message; invalid → 422.

## Quyết định

- **Bảng**: `subscription_program_selections` gắn `subscription_id` (không chỉ user) để trace chọn bằng gói nào.
- **POST = replace** toàn bộ selection của subscription hiện tại (sync).
- **All Access**: không POST; GET vẫn trả quyền lesson types.
- **`allowed_lesson_types`**: tính từ plan, không persist DB.
- **Subscription hợp lệ**: trial | active | grace_period (giống `validSubscription`).
- **2026-05-29 — API purchased vs selection** → Tách `GET /programs/purchased` (màn đã mua, mọi status subscription). Giữ `GET|POST /programs/selection` cho flow chọn program (chỉ subscription hợp lệ).
- **2026-05-29 — Ngày subscription** → `started_at` = `created_at`; `renews_at` = `expires_at` khi còn auto-renew và status hợp lệ, else `null`.
- **2026-05-29 — Figma purchased_program** → Không API renew mới; bổ sung field purchased + `POST /subscriptions/cancel`. Badge plan: FE map từ `plan` enum.
- **2026-05-29 — Cancel renewal** → Cùng semantics `subscription-provider-cancel` (Google provider + webhook DB).

## Liên quan

- [`docs/specs/plan_program.md`](../../plan_program.md)
- [`program-list/spec.md`](../program-list/spec.md)
- `app/Share/Models/Subscription.php`, `app/Share/Enums/Plan.php`, `app/Share/Enums/LessonType.php`
- Figma: `docs/specs/web/figma/purchased_program_1.png`, `purchased_program_2.png`
- [`subscription-provider-cancel/spec.md`](../subscription-provider-cancel/spec.md)
