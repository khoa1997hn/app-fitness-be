# config/ — env & config conventions

## Env qua config (rule 09)
- `env(...)` CHỈ được dùng trong file `config/*.php`. Code logic gọi `config(...)`.
- Lý do: `php artisan config:cache` (production) sẽ làm `env()` trong code logic trả `null`.

## Đặt tên env
- BẮT BUỘC prefix provider/module: `AWS_`, `GOOGLE_`, `APPSTORE_`, `PLAN_`, `PAYMENT_`, `JWT_`, `L5_SWAGGER_`, `DB_`, `REDIS_`, `MAIL_`.
- CẤM tên mơ hồ: `KEY`, `SECRET`, `TOKEN`, `URL` đứng riêng.
- Nested theo module: `<MODULE>_<SUBKEY>_<DETAIL>` (ví dụ `PLAN_BASIC_GOOGLE_ITEM_ID`).
- UPPER_SNAKE_CASE.

## Khi thêm env mới
1. Thêm vào CẢ `.env` lẫn `.env.example`. Lệch key → fail review.
2. `.env.example` ghi value mẫu hoặc rỗng (KHÔNG secret thật).
3. Thêm mapping vào file config tương ứng. Chưa có file → tạo mới.

## Tổ chức file config
- 1 provider/module = 1 file config riêng. Tên kebab-case: `aws.php`, `google-play.php`, `appstore.php`, `payment.php`, `subscription.php`.
- KHÔNG dồn provider vào `services.php` mặc định (chỉ giữ Laravel core: mail, stripe...).
- Đã có sẵn: `jwt.php`, `liap.php`, `translatable.php`, `app_file.php`, `app_payment.php`.

## File config liên quan FileType
- Khi thêm `FileType::<Name>` → BẮT BUỘC thêm entry vào `app_file.php` cùng commit (3 key: `prefix_path`, `allow_mimetypes`, `allow_max_size`).

Chi tiết: [`docs/rules/09-magic-and-env.md`](../docs/rules/09-magic-and-env.md), [`12-file-upload.md`](../docs/rules/12-file-upload.md).
