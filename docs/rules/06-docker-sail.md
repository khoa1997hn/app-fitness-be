# Docker & Sail commands

Mọi lệnh PHP/Composer/Artisan **phải chạy qua Laravel Sail**.

## Mẫu lệnh

```bash
sail exec --user sail laravel.test <command>
```

### Ví dụ

```bash
sail exec --user sail laravel.test php artisan make:migration create_xxx_table
sail exec --user sail laravel.test composer install
sail exec --user sail laravel.test php artisan migrate
sail exec --user sail laravel.test vendor/bin/pint
```

## Trước khi chạy

Nếu Sail chưa chạy → tự động `sail up -d` trước.

```bash
sail up -d
```

## Quan trọng

- Phải dùng Laravel artisan commands khi có sẵn (KHÔNG tự code file migration/model/controller).
- Mọi migration BẮT BUỘC qua `php artisan make:migration`.

## LocalStack S3 (local)

- Service `localstack` trong `docker-compose.yaml` — giả lập S3 cho dev.
- Init bucket: `docker/localstack/init-s3.sh` (mount vào `/etc/localstack/init/ready.d/`).
- `.env.example` đã preset `AWS_ENDPOINT=http://localstack:4566`, `AWS_USE_PATH_STYLE_ENDPOINT=true`, bucket `fitness-local`.
- Sau `sail up -d`, presigned upload/get hoạt động ngay nếu copy env mẫu.
