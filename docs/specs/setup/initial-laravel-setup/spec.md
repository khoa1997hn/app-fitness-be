# Spec: Dựng base Laravel 12 với Sail

> Spec lịch sử. Project đã hoàn thành phần này. Giữ lại để tham chiếu.

## Bối cảnh

Tạo source Laravel 12 sạch để bắt đầu dự án Fitness backend. Yêu cầu KHÔNG cài PHP/Composer trên máy local — toàn bộ qua Docker.

## Phạm vi

### In-scope
- Cài Laravel 12 (`composer create-project laravel/laravel:^12.0 .` qua Docker).
- Cài Laravel Sail (`php artisan sail:install`) với services: `laravel.test`, `mysql`, `redis`, `phpmyadmin`.
- Cài Laravel Pint cho format.
- Tạo `.cursorrules` ban đầu (đã được thay thế bằng `.cursor/` + `docs/` ở bước sau).

### Out-of-scope
- Auth.
- CRUD nghiệp vụ.
- Production hardening cho docker-compose.

## Nghiệp vụ

Không có nghiệp vụ — đây là spec setup.

## Input / Output

### Input
- Empty repo.

### Output
- Source Laravel 12 chạy được bằng `./vendor/bin/sail up -d`.
- `docker-compose.yaml` quản lý bởi Sail.
- Pint chạy được: `./vendor/bin/pint`.

## Acceptance criteria

- [x] `sail up -d` mở được container thành công.
- [x] Truy cập `http://localhost` thấy Laravel welcome.
- [x] phpMyAdmin tại `http://localhost:8080` kết nối được MySQL.
- [x] `vendor/bin/pint` không báo lỗi format trên code mặc định.

## Quyết định

- 2025-01 — Stack: Laravel 12 + Sail (mysql/redis/phpmyadmin), Pint cho format. Controller-first, không Repository.
- 2025-01 — Code style rules ban đầu lưu trong `.cursorrules` root.
- 2026-05-29 — Chuyển toàn bộ rule từ `.cursorrules` sang `.cursor/` + `docs/` (xem `docs/README.md`). `.cursorrules` đã xóa.

## Liên quan

- `README.md` (root) — hướng dẫn setup môi trường dev/production.
- `docs/rules/06-docker-sail.md` — chuẩn dùng Sail.
- `docker-compose.yaml` — service definition.
