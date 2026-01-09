# Visa Website

## Yêu cầu hệ thống

### Với Laravel Sail
- Docker và Docker Compose
- MySQL >= 8.0 (chạy trong container)

### Không dùng Sail
- PHP >= 8.5
- Composer
- MySQL >= 8.0
- Redis (tùy chọn)

## Setup môi trường phát triển

### Cách 1: Sử dụng Laravel Sail (Khuyến nghị)

#### Bước 1: Cài đặt dependencies

```bash
# Sử dụng Docker để chạy composer install (không cần cài composer trên máy)
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    composer:latest install
```

#### Bước 2: Cấu hình môi trường

```bash
cp .env.example .env
```

Sửa file `.env` với các thông tin database và cấu hình cần thiết.

#### Bước 3: Khởi động Sail

```bash
./vendor/bin/sail up -d
```

#### Bước 4: Vào container và chạy các lệnh

```bash
# Vào bash của container
./vendor/bin/sail exec laravel.test bash

# Trong container, chạy các lệnh:
php artisan key:generate
php artisan migrate:fresh --seed
```

#### Bước 5: Truy cập ứng dụng

- Website: http://localhost
- Admin: http://localhost/admin/login
- phpMyAdmin: http://localhost:8080

**Lưu ý:** Khi làm việc với Sail, nên vào bash container trước:
```bash
./vendor/bin/sail exec laravel.test bash
# Sau đó chạy các lệnh PHP/Artisan bình thường trong container
```

### Cách 2: Setup thủ công (không dùng Sail)

#### Bước 1: Cài đặt dependencies

```bash
composer install
```

#### Bước 2: Cấu hình môi trường

```bash
cp .env.example .env
php artisan key:generate
```

Sửa file `.env` với các thông tin database và cấu hình cần thiết:
- `DB_CONNECTION=mysql`
- `DB_HOST=127.0.0.1`
- `DB_PORT=3306`
- `DB_DATABASE=your_database`
- `DB_USERNAME=your_username`
- `DB_PASSWORD=your_password`

#### Bước 3: Tạo database

Tạo database MySQL với tên đã cấu hình trong `.env`.

#### Bước 4: Chạy migration và seed dữ liệu

```bash
php artisan migrate:fresh --seed
```

#### Bước 5: Khởi động server

```bash
php artisan serve
```

Truy cập: http://localhost:8000

## Setup cho Production

### Yêu cầu

- PHP 8.5 với PHP-FPM
- Nginx
- MySQL >= 8.0
- Composer
- Redis (tùy chọn)

### Bước 1: Clone và cài đặt

```bash
git clone <repository-url>
cd visa-website
composer install --optimize-autoloader --no-dev
```

### Bước 2: Cấu hình môi trường

```bash
cp .env.example .env
php artisan key:generate
```

Sửa file `.env`:
- `APP_ENV=production`
- `APP_DEBUG=false`
- Cấu hình database, cache, queue, etc.

### Bước 3: Tạo database và chạy migration

```bash
php artisan migrate --force
php artisan db:seed
```

### Bước 4: Tối ưu hóa

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Bước 5: Cấu hình Nginx

Tạo file cấu hình Nginx (ví dụ: `/etc/nginx/sites-available/visa-website`):

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/visa-website/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.5-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Kích hoạt site:
```bash
sudo ln -s /etc/nginx/sites-available/visa-website /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### Bước 6: Cấu hình PHP-FPM

Đảm bảo PHP-FPM 8.5 đang chạy:
```bash
sudo systemctl status php8.5-fpm
sudo systemctl enable php8.5-fpm
sudo systemctl start php8.5-fpm
```

### Bước 7: Phân quyền

```bash
sudo chown -R www-data:www-data /path/to/visa-website/storage
sudo chown -R www-data:www-data /path/to/visa-website/bootstrap/cache
sudo chmod -R 775 /path/to/visa-website/storage
sudo chmod -R 775 /path/to/visa-website/bootstrap/cache
```

## Seed dữ liệu

### Seed dữ liệu bắt buộc

```bash
# Với Sail (trong container)
php artisan db:seed

# Thủ công
php artisan db:seed
```

### Seed dữ liệu fake (cho testing)

```bash
# Với Sail (trong container)
php artisan db:seed --class=FakeDatabaseSeeder

# Thủ công
php artisan db:seed --class=FakeDatabaseSeeder
```

## Tài khoản mặc định

Sau khi chạy seed dữ liệu bắt buộc, đăng nhập admin với:
- Username: `admin`
- Password: `password`

## Dừng Sail

```bash
./vendor/bin/sail down
```

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
