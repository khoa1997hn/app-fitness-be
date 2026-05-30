# Plan: choose-program

## Tóm tắt

API GET/POST `/api/v1/programs/selection` + bảng `subscription_program_selections` + service map quyền plan.

## Thiết kế

1. **Migration** `subscription_program_selections`.
2. **Model** `SubscriptionProgramSelection` + relations trên `Subscription`, `Program`, `User`.
3. **Service** `ProgramSelectionService` — `getLimits(Plan)`, `getStatus(User)`, `syncSelection(User, array $programIds)`.
4. **Controller** `ProgramSelectionController` — `show`, `store`.
5. **Routes** trong `auth:api` group.
6. **OpenAPI** + `l5-swagger:generate`.
7. **Seeder** (optional): gán selection mẫu cho user có subscription trong fake data.

## File chạm

- `database/migrations/*_create_subscription_program_selections_table.php`
- `app/Share/Models/SubscriptionProgramSelection.php`
- `app/Share/Services/Program/ProgramSelectionService.php`
- `app/Web/Http/Controllers/API/V1/ProgramSelectionController.php`
- `routes/api.php`
- `docs/project-overview.md` (module đã có)

## Rủi ro

- User đổi plan giữa kỳ: selection cũ vẫn gắn subscription record — chấp nhận theo spec (out-of-scope auto reset).

## Update 2026-05-29 — GET /programs/purchased

- Thêm `ProgramSelectionService::getPurchased(User)` — subscription (mọi status) + programs.
- `renews_at` = `expires_at` khi `auto_renew` + status hợp lệ.
- Plan `all` → list toàn bộ program; basic/plus → selections.
- Route + OpenAPI trên `ProgramSelectionController::purchased`.

## Update 2026-05-29 — Figma purchased_program

- Bổ sung GET /programs/purchased: amount, currency, expires_at, auto_renew, provider, show_plan_ends_notice, can_cancel_renewal, can_renew.
- Thêm POST /subscriptions/cancel (SubscriptionManager, Google provider cancel).

## Update 2026-05-30 — GET /subscriptions/me thay GET /programs/purchased

- Bỏ `GET /programs/purchased` (ProgramSelectionController@purchased) + route.
- Đổi tên `SubscriptionCancelController` → `SubscriptionController`; thêm `show()` method cho `GET /subscriptions/me`.
- `ProgramSelectionService`: thêm `getSubscriptionInfo(User)` → trả subscription + selected_programs (reuse logic hiện có); All Access → `selected_programs: null`.
- Xóa file `purchased_program_1/2.png` và references trong spec.
- OpenAPI: thêm `GET /subscriptions/me`, xóa `GET /programs/purchased`.
