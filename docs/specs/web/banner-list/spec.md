# Spec: API list Banner (Web V1)

## Bối cảnh

App native hiển thị banner carousel trên Home. Endpoint `GET /api/v1/banners` đã có trong `BannerController`; admin CRUD xem `docs/specs/admin/banner-management/spec.md`.

## Phạm vi

### In-scope
- `GET /api/v1/banners` — danh sách banner **active** theo locale (`x-locale`), sort `order` asc, `id` desc.
- Response: `id`, `image` (File + presigned `url`), `link_url`.
- **Không** query filter.

### Out-of-scope
- Admin CRUD banner.
- Phân trang.

## Nghiệp vụ

1. Client gọi `GET /banners` (optional `x-locale`).
2. BE lấy banner có `is_active = true` (translation locale hiện tại).
3. Sort: `order` tăng dần, `id` giảm dần.
4. Trả toàn bộ banner active — **không** filter theo `id`.

## Input / Output

### Input
- `x-locale` (optional).

### Output (200)
```json
{
  "success": true,
  "data": [
    { "id": 1, "image": { "path": "...", "url": "..." }, "link_url": "https://..." }
  ]
}
```

## Acceptance criteria

- [ ] Chỉ banner `is_active` (locale) được trả về.
- [ ] Sort đúng order asc, id desc.
- [ ] Không có query param `id`.

## Quyết định

- **2026-06-06** — Bỏ query filter `id` (1 hoặc nhiều ids); client luôn nhận full list active.

## Liên quan

- `app/Web/Http/Controllers/API/V1/BannerController.php`
- `docs/specs/admin/banner-management/spec.md`
