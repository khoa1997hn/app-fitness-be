# AI PROMPT – DỰNG SOURCE LARAVEL 12 BASE (DÙNG DOCKER + SAIL)

Bạn là AI lập trình viên backend Laravel senior.

## MỤC TIÊU
Dựng **source Laravel 12 cơ bản** theo chuẩn Laravel gốc, **KHÔNG cài PHP hay Composer trên máy local**.
Toàn bộ thao tác tạo project và cài dependency **phải chạy bằng Docker**.

## YÊU CẦU CHUNG
1. Laravel version: **Laravel 12**
2. Dùng **Docker để chạy Composer**, sau đó cài **Laravel Sail**
3. Sau này local sẽ chạy project bằng `./vendor/bin/sail`
4. Code **đơn giản, thực tế**, không bịa spec, không over-engineering
5. **Logic đặt trực tiếp trong Controller**
   - Chỉ dùng Service khi thật sự cần tái sử dụng
   - **KHÔNG dùng Repository pattern**
6. Dùng **Laravel Pint** để format code
7. Tạo file `.cursorrules` ngay từ đầu

---

## CÁCH TẠO PROJECT (BẮT BUỘC)
- Không cài `php`, `composer` trên máy local
- Tạo project Laravel bằng Docker:

Ví dụ hướng tiếp cận:
- Dùng image `composer:latest` hoặc `laravelsail/phpXX-composer`
- Mount source code ra ngoài
- Chạy:
```

composer create-project laravel/laravel:^12.0 .

```

Sau khi có source:
- Cài Laravel Sail:
```

php artisan sail:install

```

---

## DOCKER / SAIL YÊU CẦU
File `docker-compose.yml` (do Sail quản lý) **phải có các service sau**:

1. `laravel.test`
2. `mysql`
3. `redis`
4. `phpmyadmin`

Yêu cầu:
- MySQL dùng image chính thức
- Redis dùng image chính thức
- phpMyAdmin kết nối được MySQL
- Port mapping rõ ràng, dễ dùng local
- Không cần production-hardening

---

## CODE STYLE & PHILOSOPHY
- Ưu tiên **đơn giản – dễ đọc – đúng chuẩn Laravel**
- Không tạo abstraction khi chưa cần
- CRUD thì viết thẳng trong Controller
- Validation dùng FormRequest khi hợp lý, còn lại dùng `$request->validate()`
- Không fake business rule

---

## LARAVEL PINT
- Cài `laravel/pint` bằng Composer
- Có thể chạy:
```

./vendor/bin/pint

```
- Code xuất ra phải tuân theo Pint default rules

---

## FILE .CURSORRULES
Tạo file `.cursorrules` ở root project với các nguyên tắc:
- Không bịa yêu cầu
- Không over-engineer
- Ưu tiên Controller-based logic
- Viết code đúng chuẩn Laravel
- Không tự ý tạo layer không được yêu cầu

---

## OUTPUT MONG MUỐN
- Source Laravel 12 chạy được bằng Sail
- Có `docker-compose.yml` (Sail)
- Có `.cursorrules`
- Chưa cần auth, chưa cần CRUD phức tạp
- Chỉ cần base project sạch, sẵn sàng phát triển

=> Hãy dựng source theo đúng các yêu cầu trên, không thêm thắt ngoài scope.
```
