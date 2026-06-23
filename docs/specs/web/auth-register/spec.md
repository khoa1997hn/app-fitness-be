# Spec: API đăng ký tài khoản

## Bối cảnh

End-user đăng ký tài khoản mới qua native app. Sau khi đăng ký thành công, user cần đăng nhập riêng để lấy JWT token.

> Persona: end-user chưa có tài khoản (public, không JWT).

## Phạm vi

### In-scope
- `POST /api/v1/auth/register` — tạo user mới với email, password, first_name, last_name; phone và dob tùy chọn.

### Out-of-scope
- Tự động đăng nhập / trả JWT sau register.
- Xác thực email.
- OAuth / social login.

## Nghiệp vụ

1. Client gửi thông tin đăng ký.
2. BE validate input.
3. Tạo user mới (password hash tự động qua model cast).
4. Trả 201 với thông tin user (không có password).

## Input / Output

### Input
| Field | Kiểu | Validation | Bắt buộc |
|---|---|---|---|
| `email` | string | `required\|email\|max:255\|unique:users` | Có |
| `password` | string | `required\|string\|min:8\|max:50` | Có |
| `first_name` | string | `required\|string\|max:255` | Có |
| `last_name` | string | `required\|string\|max:255` | Có |
| `phone` | string | `nullable\|string\|max:255` | Không |
| `dob` | date (Y-m-d) | `nullable\|date:Y-m-d` | Không |

### Output (201)
```json
{
  "success": true,
  "message": "...",
  "data": {
    "id": 1,
    "email": "user@example.com",
    "first_name": "Nguyễn",
    "last_name": "Văn A",
    "phone": null,
    "dob": null,
    "created_at": "...",
    "updated_at": "..."
  }
}
```

### Lỗi
- Validation fail → 422.
- Email trùng → 422.

## Acceptance criteria

- [ ] `POST /auth/register` với email/password/first_name/last_name → 201, user được tạo.
- [ ] Không gửi `phone` và `dob` → 201, cả hai field lưu `null`.
- [ ] Gửi `phone` và `dob` hợp lệ → 201, lưu đúng giá trị.
- [ ] Email trùng → 422.
- [ ] Thiếu field bắt buộc → 422.

## API Design

### POST /api/v1/auth/register
- **Auth**: public (không JWT)
- **Request**: `{ email, password, first_name, last_name, phone?, dob? }`
- **Response 201**: user object (id, email, first_name, last_name, phone, dob, created_at, updated_at)
- **Errors**: 422

## Quyết định

- **2026-01-30** — Endpoint `POST /api/v1/auth/register`, public, không trả JWT.
- **2026-06-23** — `phone` và `dob` không bắt buộc khi đăng ký → cả hai `nullable`; không gửi thì lưu `null`. *(Update từ user)*

## Update 2026-06-23

### Yêu cầu
Bỏ required số điện thoại (`phone`) và ngày sinh (`dob`) ở API đăng ký tài khoản.

### Thay đổi
- Validation: `phone` giữ `nullable`; `dob` đổi từ `required` → `nullable`.
- OpenAPI: bỏ `dob` khỏi `required` array; đánh dấu `dob` nullable.
- DB: không đổi (cột `phone`, `dob` đã nullable từ migration gốc).

## Liên quan

- `app/Web/Http/Controllers/API/V1/Auth/RegistrationController.php`
- `app/Share/Models/User.php`
- `database/migrations/0001_01_01_000000_create_users_table.php`
- `docs/specs/web/password-reset/spec.md` (password rule giống register)
