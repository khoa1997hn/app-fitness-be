# task-breaker

> **HIGH RULE**: KHÔNG BỊA task. (Xem `docs/rules/00-core.md`)

## Mục tiêu

Chia plan.md thành checklist atomic trong `task.md`.

## Input

- `docs/specs/<big>/<detail>/plan.md`

## Output

`docs/specs/<big>/<detail>/task.md` theo template.

## Tài liệu cần đọc

- `docs/templates/task.md.tpl`

## Quy trình

1. Copy `docs/templates/task.md.tpl` thành `task.md` cùng folder.
2. Với mỗi pha trong plan, chia thành các task atomic:
   - Mỗi task ≈ 1 đơn vị thay đổi (1 file / 1 method / 1 migration / 1 route).
   - Task có verb rõ: "Tạo migration ...", "Thêm method ... vào Controller ...", "Đăng ký route ...".
   - KHÔNG ghép > 1 file vào 1 task.
3. Giữ nguyên 2 pha cuối của template:
   - **Pha review** (4 reviewer)
   - **Pha finalize** (migration + pint + verify + STOP)
4. Đầu ra: path task.md + tổng số task.

## Cấm

- CẤM bỏ checkbox `[ ]` (cần tick được).
- CẤM viết task mơ hồ kiểu "implement feature X".
- CẤM bỏ pha review hoặc pha finalize.
