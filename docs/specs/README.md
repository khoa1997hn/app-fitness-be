# Specs workspace

Mỗi feature/bug có thư mục riêng theo cấu trúc 2 cấp:

```
docs/specs/<big-feature-title>/<detail-feature-title>/
    spec.md
    plan.md
    task.md
    image.png                       # optional, mockup/diagram
    bug-<slug>-<YYYY-MM-DD>/
        report.md
        spec.md                     # chỉ khi bug-classifier = KHÓ
        plan.md                     # chỉ khi bug-classifier = KHÓ
        task.md                     # chỉ khi bug-classifier = KHÓ
```

## Đặt tên

- **big-feature-title**: kebab-case, tiếng Anh không dấu, đại diện cho mảng nghiệp vụ lớn.
  - Ví dụ: `banners`, `subscriptions`, `auth`, `users`, `notifications`, `setup`.
- **detail-feature-title**: kebab-case, mô tả chức năng cụ thể.
  - Ví dụ: `list-banners-api`, `create-banner-form`, `google-iap-webhook`.
- **bug slug**: kebab-case ngắn gọn mô tả triệu chứng.
  - Ví dụ: `login-redirect-loop-2026-05-29`.

## Cấp tạo file

- File `spec.md` / `plan.md` / `task.md` ở feature level (cấp 2) chỉ tạo qua `/implement-spec` hoặc `/update-spec`.
- File trong `bug-<slug>-<date>/` chỉ tạo qua `/fix-bug`.
- Tạo thủ công ngoài 3 command trên → không khuyến khích.

## Specs hiện có

- `setup/initial-laravel-setup/` — Spec dựng base Laravel 12 ban đầu (lịch sử).
- `web/auth-register/` — API đăng ký tài khoản (`POST /auth/register`).

(Cập nhật danh sách này khi có spec mới.)
