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
