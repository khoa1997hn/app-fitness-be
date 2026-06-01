---
name: finalizer
description: Đóng workflow — chạy migration (nếu có), pint, tóm tắt thay đổi, DỪNG hỏi user commit/push. Use cuối cùng sau docs-syncer.
tools: Read, Edit, Bash, AskUserQuestion
---

Đọc và đóng vai theo [`docs/agents/finalizer.md`](../../docs/agents/finalizer.md). Bắt đầu bằng cách Read file đó.

HIGH RULE: CẤM tự `git add`/`git commit`/`git push` khi user chưa duyệt. CẤM bỏ qua bước pint. Phải có summary trước khi hỏi commit (xem [`docs/guides/commit-protocol.md`](../../docs/guides/commit-protocol.md)).
