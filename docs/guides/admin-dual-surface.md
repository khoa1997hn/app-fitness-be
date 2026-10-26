# Admin dual surface — Blade + FE React

## Quy tắc phân bổ (chốt)

| Surface | Khi nào | Spec UI |
|---------|---------|---------|
| **FE React** (`surfaces: fe`) | Có [`fitness-web/docs/screens/admin/kore-*.md`](../../../fitness-web/docs/screens/admin/) | Screen doc `kore-*` + nghiệp vụ [`docs/specs/admin/`](../../specs/admin/) |
| **Blade** (`surfaces: blade`) | Admin **không** có `kore-*.md` tương ứng | Spec admin BE + [`docs/rules/05-admin-blade.md`](../rules/05-admin-blade.md) |

File `docs/screens/admin/*.md` **không** prefix `kore-` = prototype cũ trên FE repo — **không** thuộc Kore video app.

Module Kore đã có Blade (program, banner, …): Blade **giữ fallback**; feature UI mới chỉ cập nhật spec `kore-*.md` và React — **không** `both` như hai sản phẩm song song.

Ma trận: [`fitness-web/docs/admin/module-matrix.md`](../../../fitness-web/docs/admin/module-matrix.md).

## Stack

| Surface | Stack | Auth |
|---------|--------|------|
| **Blade** | Laravel + Dashcode, `routes/admin.php` | Session `auth:admin` |
| **FE React** | `fitness-web`, `/admin/...` | **TODO(ask)** — Admin JSON hoặc session SPA |

Nghiệp vụ **một nguồn**: spec admin BE. React **không** tự thêm field/validation.

## Khai báo trong spec BE

Section **`## Admin surfaces`**: `surfaces: fe` hoặc `blade`, link `kore-*.md` nếu `fe`.

## Thứ tự triển khai (module `fe`)

1. Nghiệp vụ đã rõ trên spec BE (Blade fallback đã chạy nếu có).
2. Admin JSON API (nếu chọn A) mirror action Blade.
3. React theo `kore-*.md` + [`fitness-web/plan/11-admin-modules.md`](../../../fitness-web/plan/11-admin-modules.md).

## Auth & API cho Admin FE

| Phương án | Ghi chú |
|-----------|---------|
| **A. Admin JSON API** | `ResponseAPI`, guard `auth:admin` — mirror Blade |
| **B. Session SPA** | `credentials: 'include'`, cùng domain BE |
| **C. Hybrid tạm** | Module `blade` only cho đến khi có API |

**TODO(ask)**: PO chọn A / B — ghi vào `module-matrix.md`.

Presigned upload: pattern `FileController` + `HandlesPresignedFileUpload`.

## Workflow

- Blade-only module: `/update-spec` trên `docs/specs/admin/...`
- Module `fe`: sửa `kore-*.md` + plan 11; API mới → spec `admin-api/<feature>` nếu cần

## Liên quan

- [`docs/rules/04-api-response.md`](../rules/04-api-response.md)
- [fullstack-flow.mdc](../../.cursor/rules/fullstack-flow.mdc)
