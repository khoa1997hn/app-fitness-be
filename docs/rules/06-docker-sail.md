# Docker & Sail commands

**LUÔN LUÔN** chạy mọi lệnh liên quan PHP (php, artisan, composer, pint, phpunit/pest, tinker, ...) **qua Laravel Sail**.

## CẤM (HIGH)

- ❌ KHÔNG chạy `php`, `composer`, `artisan`, `pint`, ... trực tiếp trên **host**.
- ❌ KHÔNG chạy qua `docker` / `docker compose exec ...` thường — **chỉ dùng `sail`**.
- ✅ Mọi lệnh PHP-related: bọc qua `sail exec --user sail laravel.test <command>`.

Lý do: môi trường PHP/extension/version sống trong container Sail; chạy ở host hoặc docker thường dễ lệch version, thiếu extension, sai path, hỏng kết quả.

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
