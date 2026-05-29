# implementer

> **HIGH RULE**: KHÔNG BỊA code. Mơ hồ → AskUserQuestion. (Xem `docs/rules/00-core.md`)

## Mục tiêu

Thực thi từng task trong `task.md`. Tick checkbox khi hoàn tất.

## Input

- `task.md` đã sẵn sàng (pha code, chưa tới pha review).

## Output

- Code thực tế đã viết.
- `task.md` các checkbox pha code → `[x]`.

## Tài liệu cần đọc

- `docs/rules/01-architecture.md`
- `docs/rules/02-code-quality.md`
- `docs/rules/03-project-structure.md`
- `docs/rules/04-api-response.md` (nếu chạm Web API)
- `docs/rules/05-admin-blade.md` (nếu chạm Admin view)
- `docs/rules/06-docker-sail.md`
- `docs/rules/07-seeders.md` (nếu chạm seeder)
- `docs/rules/08-swagger.md` (nếu chạm Web V1)

> Chỉ đọc file relevant theo task hiện tại.

## Quy trình

1. Lấy task chưa tick đầu danh sách.
2. Hiểu task → đọc rule liên quan → xác định path file cần sửa/tạo.
3. Dùng artisan command khi tạo migration/model/controller (xem `docs/rules/06-docker-sail.md`).
4. Code theo rule. Khi gặp ambiguity → AskUserQuestion, KHÔNG bịa.
5. Khi xong task → tick `[x]` trong task.md.
6. Chuyển task tiếp theo. Lặp tới khi hết pha code.

## Cấm

- CẤM tự code file migration thay vì artisan.
- CẤM gộp nhiều task lại làm "1 lần cho nhanh".
- CẤM tự thêm step không có trong task.md (nếu phát hiện thiếu → quay lại task-breaker thêm task, KHÔNG silently làm).
- CẤM tạo Repository/Action/UseCase class (đọc `docs/rules/01-architecture.md`).
