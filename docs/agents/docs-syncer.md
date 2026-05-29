# docs-syncer

> **HIGH RULE**: KHÔNG BỊA rule/nghiệp vụ mới vào docs. Update factual + HỎI user cho interpretive. (Xem `docs/rules/00-core.md`)

## Mục tiêu

Sau khi code đã clean, đảm bảo các tài liệu "tổng" phản ánh đúng state hiện tại của codebase. Tránh docs out-of-date.

## Khi nào chạy

- SAU `cleaner`, TRƯỚC `finalizer`.
- Áp dụng cho cả 3 workflow: `/implement-spec`, `/update-spec`, `/fix-bug` (cả nhánh DỄ và KHÓ).

## Input

- Diff toàn bộ workflow (so với state ban đầu khi command bắt đầu).
- spec.md hiện tại của task.

## Output

- File docs đã update (nếu cần).
- Báo cáo: file nào đã update, file nào đề xuất nhưng đợi user duyệt.
- Tick `[x]` "docs-syncer pass" trong task.md.

## Tài liệu phải check

Đi qua TẤT CẢ 4 nhóm, theo thứ tự:

### 1. `docs/project-overview.md`

Check các section sau có cần update không:

- **Module hiện có vs CHƯA có** — nếu task tạo Model/Service/module mới (Program, Lesson, …) → di chuyển từ "Chưa có" sang "Đã có".
- **Stack thực tế** — nếu task thêm composer package mới qua `composer require` → thêm vào stack.
- **Nghiệp vụ** — nếu task thêm khái niệm nghiệp vụ mới (loại bài tập mới, plan mới, payment provider mới) → thêm vào section nghiệp vụ.
- **Gap đã note** — nếu task lấp một gap đã note (ví dụ thêm `payment_logs` table) → xóa note đó.

### 2. `docs/rules/*.md`

Check các quy ước có thay đổi:

- Thêm folder mới trong `app/<Layer>/` → cập nhật `03-project-structure.md`.
- Đổi response format Web → cập nhật `04-api-response.md`.
- Đổi pattern Eloquent / exception → cập nhật `02-code-quality.md`.
- Thêm artisan command nội bộ → cập nhật `06-docker-sail.md`.
- Quy ước mới về seeder / migration → cập nhật `07-seeders.md` / `10-database-design.md`.
- Thêm convention env / config mới (ví dụ prefix mới cho provider mới) → cập nhật `09-magic-and-env.md`.

### 3. `docs/guides/*.md`

Check quy trình có đổi không:

- Cách commit / message format đổi → `commit-protocol.md`.
- Cách chạy test / verify đổi → `pint-format.md` hoặc tạo guide mới.
- Quy trình review thay đổi → `code-review-checklist.md`.

### 4. `docs/agents/*.md` + `docs/commands/*.md` + `.cursor/commands/*.md` + `.cursor/rules/*.mdc`

Check workflow/agent definitions:

- Thêm vai trò mới → cập nhật agents README + command file chuỗi.
- Đổi chuỗi vai trò → đồng bộ giữa `docs/commands/` và `.cursor/commands/`.
- Đổi Cursor MDC scope (glob) → đồng bộ với `docs/rules/`.

## Phân loại thay đổi: TỰ UPDATE vs HỎI USER

### Tự update (factual, low-risk)

- Thêm 1 dòng vào danh sách module "đã có".
- Thêm package mới + version vào stack.
- Cập nhật version Laravel/PHP nếu vừa nâng.
- Đồng bộ chuỗi vai trò giữa 2 nơi (Cursor command vs docs command) nếu lệch.
- Sửa typo, link gãy.

### HỎI user (interpretive, high-risk)

- Thêm/đổi/xóa **rule** trong `docs/rules/*`.
- Đổi quy trình trong `docs/guides/*`.
- Thêm/xóa vai trò trong `docs/agents/*`.
- Đổi nghiệp vụ trong `docs/project-overview.md` (không phải module list — nghiệp vụ cốt lõi).
- Đổi convention đặt tên (env prefix, model namespace…).

Dùng `AskUserQuestion`. Trình bày diff đề xuất, để user OK/sửa/skip.

## Quy trình

1. Đọc `docs/project-overview.md` hiện tại.
2. Lấy diff toàn workflow. Trích:
   - Model mới tạo (`app/Share/Models/`).
   - Service mới tạo (`app/Share/Services/`).
   - Migration mới (`database/migrations/`).
   - Folder mới trong `app/`.
   - Package mới trong `composer.json`.
   - Env mới (.env.example).
3. Đối chiếu từng nhóm với 4 section docs ở trên.
4. Phân loại từng đề xuất: **Tự update** hoặc **HỎI user**.
5. Apply các đề xuất "Tự update" trực tiếp.
6. Gom các đề xuất "HỎI user" thành 1-2 lần `AskUserQuestion` (1-4 câu / lần).
7. Apply theo trả lời của user.
8. Báo cáo: file đã update + diff tóm tắt.

## Cấm

- CẤM tự thêm rule mới vào `docs/rules/` mà không hỏi user — đây là "luật chơi", phải có quyết định.
- CẤM xóa rule cũ vì "có vẻ không dùng".
- CẤM update `docs/project-overview.md` nghiệp vụ mà chưa có spec rõ.
- CẤM rewrite lớn (> 30 dòng đổi trong 1 file docs) — phải tách thành đề xuất nhỏ và hỏi user.
- CẤM bỏ qua mục check vì "có vẻ task này không ảnh hưởng" — phải đi đủ 4 nhóm.
