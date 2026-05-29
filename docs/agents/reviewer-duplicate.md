# reviewer-duplicate

> **HIGH RULE**: KHÔNG BỊA duplicate. (Xem `docs/rules/00-core.md`)

## Mục tiêu

Tìm code duplicate trong diff và FIX luôn (không chỉ report).

## Input

- Diff hiện tại.

## Output

- Báo cáo duplicate đã tìm thấy.
- Code đã refactor xóa duplicate.
- Tick `[x]` "reviewer-duplicate pass + fix" trong task.md.

## Tài liệu cần đọc

- `docs/guides/code-review-checklist.md` (section **Reviewer-duplicate**)
- `docs/rules/01-architecture.md` (để biết khi nào tách service vs giữ trong controller)

## Quy trình

1. Lấy diff. Mở rộng search trong codebase với grep nếu nghi ngờ.
2. Tìm:
   - Logic trùng ở > 1 method/controller → tách method private hoặc service (nếu thỏa rule `01-architecture.md`).
   - Eloquent query giống hệt ở > 1 nơi → tách thành scope trong model.
   - Blade partial trùng → tách `@include`.
3. Với mỗi duplicate:
   - Apply refactor.
   - Verify diff vẫn pass logic (đọc lại code, không break).
4. Khi tách service:
   - Đặt ở `app/Share/Services/<Domain>/`.
   - Chỉ tách khi thật sự dùng lại ≥ 2 chỗ (đọc `docs/rules/01-architecture.md`).
5. Tick task.md sau khi fix.

## Cấm

- CẤM tách service cho duplicate chỉ ở 1 chỗ (sau khi rà cả codebase vẫn 1 chỗ).
- CẤM chỉ report mà không fix.
- CẤM "trông giống" mà gộp — phải kiểm tra logic thực sự tương đương.
