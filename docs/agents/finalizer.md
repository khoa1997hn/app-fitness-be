# finalizer

> **HIGH RULE**: KHÔNG tự commit/push. (Xem `docs/guides/commit-protocol.md`)

## Mục tiêu

Đóng workflow: chạy migration, format code, tóm tắt thay đổi, DỪNG hỏi user.

## Input

- Pha code + 4 pha review đã pass.

## Output

- Migration đã chạy (nếu có).
- Pint đã chạy.
- Báo cáo tóm tắt thay đổi.
- AskUserQuestion: commit? push?

## Tài liệu cần đọc

- `docs/rules/06-docker-sail.md`
- `docs/guides/pint-format.md`
- `docs/guides/commit-protocol.md`

## Quy trình

1. Đảm bảo Sail đang chạy. Nếu không → `sail up -d`.
2. Nếu có migration mới (check `database/migrations/` trong diff):
   ```bash
   sail exec --user sail laravel.test php artisan migrate
   ```
3. Chạy pint:
   ```bash
   sail exec --user sail laravel.test vendor/bin/pint
   ```
4. Verify pint pass:
   ```bash
   sail exec --user sail laravel.test vendor/bin/pint --test
   ```
5. Tóm tắt thay đổi (file đã sửa, endpoint mới, migration mới, v.v.).
6. Tick các checkbox còn lại trong `task.md` (Pha finalize, trừ checkbox commit).
7. **DỪNG** — gọi `AskUserQuestion`:
   - Có commit không? (Yes / No)
   - Nếu Yes → đề xuất message commit (đọc `docs/guides/commit-protocol.md` để biết format).
   - Có push sau commit không? (Yes / No)

## Cấm

- CẤM tự `git add` / `git commit` / `git push` khi chưa được user duyệt.
- CẤM bỏ qua bước pint.
- CẤM kết thúc mà không có summary.
