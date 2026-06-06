# Spec: Fake subscription seeder (FakeDatabaseSeeder)

## Bối cảnh

`FakeDatabaseSeeder` đã seed 100 user qua `UsersSeeder` nhưng **chưa** có `subscriptions` / `subscription_program_selections`. API cần `validSubscription` (trial/active/grace_period) và Basic/Plus cần program đã chọn mới xem video được.

## Phạm vi

### In-scope
- Seeder mới `SubscriptionsSeeder` đăng ký trong `FakeDatabaseSeeder`.
- Chạy **sau** `ProgramsSeeder` (cần program id cho selection).
- **100%** user fake được gán đúng 1 subscription (`user_id` unique).
- Dùng `SubscriptionService::adminUpsert` + `ProgramSelectionService::adminSyncSelections` (sync `users.plan`, `users.subscription_status`).
- Provider: `admin` (tự sinh khi create qua service).
- Status: `active`; `expires_at` = now + 1 năm; `auto_renew` = true.
- Phân bố plan (weighted random): ~70% All, ~15% Basic, ~15% Plus.
- Basic: random chọn **1** program; Plus: random **2** program; All: không selection (clear).

### Out-of-scope
- Google/Apple IAP records (`google_subscriptions`, `apple_subscriptions`).
- User không có subscription (0% trong fake batch).
- Status trial/expired/cancelled mix.

## Nghiệp vụ

1. Load tất cả `User` (từ `UsersSeeder`).
2. Load danh sách `program_id` hiện có.
3. Mỗi user: random plan theo tỷ lệ → `adminUpsert`.
4. Nếu Basic/Plus: shuffle program ids, lấy đủ `max_programs` → `adminSyncSelections`.
5. Nếu All: `adminSyncSelections` với `[]` (clear selections).

## Input / Output

### Input
- Users đã seed (100).
- Programs đã seed (≥ 2 id cho Plus).

### Output (DB)
- `subscriptions`: 1 row / user.
- `subscription_program_selections`: 0 (All) hoặc 1 (Basic) hoặc 2 (Plus) / user.
- `users.plan`, `users.subscription_status` đồng bộ.

## Acceptance criteria

- [ ] `db:seed --class=FakeDatabaseSeeder` → 100 subscriptions.
- [ ] ~70% user plan `all`, không có program selection.
- [ ] User `basic` có đúng 1 selection; `plus` có đúng 2.
- [ ] `GET /subscriptions/me` (user seed) trả subscription active.
- [ ] User basic/plus có thể play video thuộc program đã chọn (nếu program seed sẵn).

## Quyết định

- **2026-06-06** — Coverage → **100%** user có subscription.
- **2026-06-06** — Plan mix → **đa số All** (~70/15/15 weighted random).
- **2026-06-06** — Basic/Plus → **auto random** program selection đủ số lượng.
- **2026-06-06** — Implementation → **reuse** `SubscriptionService` + `ProgramSelectionService`.
- **2026-06-06** — Status → **active**, expires +1 năm, auto_renew true.

## Liên quan

- `database/seeders/FakeDatabaseSeeder.php`
- `docs/rules/07-seeders.md`, `docs/rules/13-subscription-iap.md`
- `docs/specs/plan_program.md`
