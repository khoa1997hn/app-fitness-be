# Swagger / OpenAPI

Tất cả endpoint trong `app/Web/Http/Controllers/API/V1` **bắt buộc** có Swagger annotations đầy đủ.

## Stack

- Package: `zircote/swagger-php` (qua L5 Swagger — đã cài).
- Base controller: `app/Web/Http/Controllers/API/V1/APIController.php`.

## Yêu cầu chi tiết

Mỗi endpoint phải có:

1. **Summary** + **Description** rõ ràng.
2. **Request body** — định nghĩa nested structure đầy đủ (không bỏ field).
3. **Response** — định nghĩa nested structure đầy đủ, khớp đúng với map field thực tế trong controller (xem `docs/rules/04-api-response.md`).
4. **Status codes** — liệt kê đầy đủ status code có thể trả về (200, 400, 401, 403, 404, 422, 500…).
5. **Validation rules** — mô tả trong annotation.
6. **Security scheme** — Bearer token cho endpoint cần auth.

## Lưu ý

- Khi response controller đổi → annotation phải đổi theo (giữ đồng bộ).
- Khi thêm endpoint mới mà thiếu annotation → fail review (xem `docs/guides/code-review-checklist.md`).
