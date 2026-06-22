# Plan: API list Banner — bỏ filter id

## Pha 1 — Controller + OpenAPI
- Xóa logic `whereIn('id', ...)` và query param `id` trong OpenAPI.
- `l5-swagger:generate`

## Verify
- [ ] `GET /api/v1/banners` trả tất cả banner active, không cần `?id=`
