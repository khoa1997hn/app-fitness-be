# Plan: Fake subscription seeder

## Phụ thuộc

- Migration: không
- Models: `User`, `Subscription`, `SubscriptionProgramSelection`, `Program`
- Services: `SubscriptionService`, `ProgramSelectionService`
- Thứ tự seed: `ProgramsSeeder` trước `SubscriptionsSeeder`

## Các pha

### Pha 1 — SubscriptionsSeeder
- Tạo `database/seeders/SubscriptionsSeeder.php`
- Weighted random plan; loop users; gọi service.

### Pha 2 — FakeDatabaseSeeder
- Thêm `SubscriptionsSeeder` sau `ProgramsSeeder`.

## Verify thủ công

- [ ] `SELECT COUNT(*) FROM subscriptions` = số user fake
- [ ] Basic user có 1 row `subscription_program_selections`
- [ ] Plus user có 2 rows
- [ ] All user có 0 selection
