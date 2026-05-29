# Command: /implement-spec

Triển khai feature MỚI theo spec-driven.

## Khi nào dùng

- User mô tả feature mới (có thể kèm path spec sẵn hoặc không).
- KHÔNG dùng để sửa spec đã code xong → dùng `/update-spec`.
- KHÔNG dùng để fix bug → dùng `/fix-bug`.

## Input

- Mô tả feature từ user (text + optional path spec).

## Output

- File trong `docs/specs/<big>/<detail>/`: `spec.md`, `plan.md`, `task.md`.
- Code đã được implement + review + format.
- DỪNG trước commit, hỏi user.

## Chuỗi vai trò

Chạy tuần tự, mỗi agent đọc prompt template của nó:

1. **spec-analyzer** → `docs/agents/spec-analyzer.md`
2. **question-asker** → `docs/agents/question-asker.md`
3. **solution-reviewer** → `docs/agents/solution-reviewer.md` *(skip nếu user chưa propose solution)*
4. **api-designer** → `docs/agents/api-designer.md`
5. **api-analyzer** → `docs/agents/api-analyzer.md`
6. **planner** → `docs/agents/planner.md`
7. **task-breaker** → `docs/agents/task-breaker.md`
8. **implementer** → `docs/agents/implementer.md`
9. **openapi-writer** → `docs/agents/openapi-writer.md` *(skip nếu không chạm Web V1)*
10. **reviewer-rules** → `docs/agents/reviewer-rules.md`
11. **reviewer-smell** → `docs/agents/reviewer-smell.md`
12. **reviewer-security** → `docs/agents/reviewer-security.md`
13. **reviewer-duplicate** → `docs/agents/reviewer-duplicate.md`
14. **cleaner** → `docs/agents/cleaner.md`
15. **docs-syncer** → `docs/agents/docs-syncer.md`
16. **finalizer** → `docs/agents/finalizer.md`

## Ràng buộc

- Không skip agent nào trừ khi user yêu cầu.
- Agent sau chỉ chạy khi agent trước báo cáo OK.
- Nếu bất kỳ agent nào gặp mơ hồ → DỪNG, AskUserQuestion, KHÔNG bịa.
- Finalizer DỪNG trước commit, hỏi user (xem `docs/guides/commit-protocol.md`).

## Báo cáo cuối

Trả về cho user:
- Path spec.md / plan.md / task.md
- Summary thay đổi code (file, endpoint, migration)
- Kết quả từng reviewer
- Đề xuất commit message
