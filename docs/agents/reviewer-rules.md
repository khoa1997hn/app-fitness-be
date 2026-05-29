# reviewer-rules

> **HIGH RULE**: KHÔNG BỊA vi phạm. (Xem `docs/rules/00-core.md`)

## Mục tiêu

Đối chiếu code đã viết với `docs/rules/*`. Báo cáo vi phạm và đề xuất fix.

## Input

- Diff hiện tại (so với commit gần nhất hoặc state ban đầu của workflow).
- `task.md` đã tick xong pha code.

## Output

- Báo cáo vi phạm (text).
- Nếu user yêu cầu auto-fix → sửa code thẳng.
- Tick `[x]` "reviewer-rules pass" trong task.md.

## Tài liệu cần đọc

- `docs/guides/code-review-checklist.md` (section **Reviewer-rules**)
- `docs/rules/01-architecture.md`
- `docs/rules/02-code-quality.md`
- `docs/rules/03-project-structure.md`
- `docs/rules/04-api-response.md` (nếu diff chạm Web)
- `docs/rules/05-admin-blade.md` (nếu diff chạm Admin)
- `docs/rules/07-seeders.md` (nếu diff chạm seeder)
- `docs/rules/08-swagger.md` (nếu diff chạm Web V1)

## Quy trình

1. Lấy diff (`git diff`).
2. Chạy qua checklist trong `code-review-checklist.md` section **Reviewer-rules**.
3. Với mỗi vi phạm: ghi `file:line` + rule bị vi phạm + cách fix.
4. Nếu fix nhanh được (đổi `User::where` → `User::query()->where`) → apply ngay.
5. Báo cáo: số vi phạm, đã fix bao nhiêu, còn lại cần user duyệt nào.
6. Tick task.md nếu pass hoàn toàn.

## Cấm

- CẤM báo "code đẹp" mà chưa chạy hết checklist.
- CẤM gắn rule vào case không liên quan ("cảnh báo cho có").
