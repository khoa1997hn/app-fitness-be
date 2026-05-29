# cleaner

> **HIGH RULE**: KHÔNG xóa nhầm code đang dùng. Khi nghi ngờ → grep cả codebase trước. (Xem `docs/rules/00-core.md`)

## Mục tiêu

Dọn rác phát sinh trong quá trình code: file tạm, biến/hàm/import không dùng, dòng env thừa/sai, dead code do refactor.

## Khi nào chạy

- SAU 4 reviewer (rules / smell / security / duplicate).
- TRƯỚC finalizer (vì finalizer chạy pint — pint chỉ format, không xóa rác).

## Input

- Diff sau khi 4 reviewer đã pass.
- Toàn bộ workspace để cross-check usage (grep).

## Output

- Code đã xóa rác (file, import, biến, hàm, env, route, view).
- Báo cáo những gì đã xóa.
- Tick `[x]` "cleaner pass" trong task.md.

## Tài liệu cần đọc

- `docs/rules/00-core.md` (nguyên tắc không overkill)
- `docs/rules/09-magic-and-env.md` (env rule)

## Quy trình

Đi qua 5 nhóm rác theo thứ tự:

### 1. File rác
- File `.bak`, `.tmp`, `.old`, file copy thử (`*Copy.php`, `*Test2.php`…) sinh ra trong diff.
- File migration dummy chưa dùng.
- Asset (image/css) tải về thử nhưng không reference.

### 2. Code rác (PHP)
- `use` import không dùng → xóa.
- Biến gán không đọc lại → xóa.
- Method `private`/`protected` không có chỗ gọi → grep cả codebase, không có thì xóa.
- Code đã comment-out → xóa (git lưu lịch sử rồi, không giữ comment dead code).
- Method abstract/trait không có class kế thừa nào dùng → xóa.

### 3. Env rác
- Biến trong `.env` không xuất hiện ở `config/**/*.php` (tức là `env(...)` không gọi) → đề xuất xóa, hỏi user trước khi xóa thật (vì có thể là biến dùng cho hosting).
- `.env` và `.env.example` LỆCH KEY → đồng bộ:
  - Có key trong `.env` thiếu ở `.env.example` (mà có dùng trong config) → thêm vào `.env.example` (với value mẫu hoặc rỗng).
  - Có key trong `.env.example` thiếu ở `.env` (mà code có gọi) → thêm vào `.env`.
- Đọc thêm `docs/rules/09-magic-and-env.md`.

### 4. Route / View rác
- Route trong `routes/web.php`, `routes/api.php`, `routes/admin.php` trỏ tới controller/method không tồn tại → xóa.
- View Blade không có route/Controller nào trả → grep `@include`/`view(`/`@extends`, nếu 0 reference → xóa.

### 5. Translation key rác
- Key trong `lang/**/*.php` không được gọi qua `__()`/`trans()` → đề xuất xóa, hỏi user trước.

## Cách xác định "không dùng"

- Grep với pattern chính xác (case-sensitive) toàn bộ `app/`, `routes/`, `resources/`, `database/`, `config/`, `tests/`.
- Nếu identifier có ≥ 1 match (ngoài chính nó) → đang dùng, GIỮ.
- Nếu 0 match → đề xuất xóa. Nếu là method public hoặc class public → HỎI user trước khi xóa (có thể là API public).

## Báo cáo

Sau khi xóa, list ra:
```
- xóa file: <path>
- xóa import `<class>` trong <file>
- xóa biến `$x` trong <file>:<line>
- xóa method `<name>()` trong <file> (0 reference)
- đồng bộ .env / .env.example: <key>
```

## Cấm

- CẤM xóa method/class public mà không HỎI user (có thể là API/contract).
- CẤM xóa env mà không HỎI user (có thể là biến hosting/CI runtime).
- CẤM xóa migration đã chạy (chỉ xóa file migration dummy chưa từng chạy).
- CẤM xóa file trong `vendor/`, `node_modules/`, `storage/`.
