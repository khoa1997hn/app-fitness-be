# Database seeders

Hai loại seeder, KHÔNG được trộn.

## `DatabaseSeeder`

- Chỉ chứa seeder **bắt buộc** khi hệ thống chạy.
- Ví dụ: `AdminsSeeder` (tài khoản admin mặc định).
- Chạy:
  ```bash
  sail exec --user sail laravel.test php artisan db:seed
  ```

## `FakeDatabaseSeeder`

- Chứa seeder dùng để **seed fake data** cho dev/test.
- Ví dụ: `UsersSeeder`, dữ liệu mẫu sản phẩm/post...
- Chạy:
  ```bash
  sail exec --user sail laravel.test php artisan db:seed --class=FakeDatabaseSeeder
  ```

## Khi tạo seeder mới

- Là dữ liệu bắt buộc khi hệ thống chạy → đăng ký trong `DatabaseSeeder`.
- Là dữ liệu fake → đăng ký trong `FakeDatabaseSeeder`.
- Không đăng ký vào cả hai.

## Fake seeder — file media trên S3

- Field file (`cover`, `thumbnail`, `image`, `file` video) lưu **object key S3** — object phải **đã tồn tại** trên bucket.
- `BannerFactory` / `ProgramsSeeder`: pool path trong **const** class, random mỗi bản ghi; user điền 2–3 key / loại sau khi upload (xem `docs/specs/shared/fake-seeder-media/spec.md`).

## Fake seeder — subscription

- `SubscriptionsSeeder`: gán subscription cho mọi user fake qua `SubscriptionService::adminUpsert` + `ProgramSelectionService::adminSyncSelections`.
- Chạy **sau** `ProgramsSeeder`. Xem `docs/specs/shared/fake-subscription-seeder/spec.md`.
