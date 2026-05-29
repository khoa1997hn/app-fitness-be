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
