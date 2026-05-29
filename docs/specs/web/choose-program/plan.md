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
